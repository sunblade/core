<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\Comments;

use OCP\Comments\IComment;
use OCP\Comments\ICommentsManager;
use OCP\IUserSession;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\UnsupportedMediaType;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Sabre plugin to handle comments:
 */
class CommentsPlugin extends ServerPlugin {
	// namespace
	const NS_OWNCLOUD = 'http://owncloud.org/ns';
	const ID_PROPERTYNAME = '{http://owncloud.org/ns}id';

	/** @var ICommentsManager  */
	protected $commentsManager;

	/** @var \Sabre\DAV\Server $server */
	private $server;

	/** @var  \OCP\IUserSession */
	protected $userSession;

	/**
	 * Comments plugin
	 *
	 * @param ICommentsManager $commentsManager
	 * @param IUserSession $userSession
	 */
	public function __construct(ICommentsManager $commentsManager, IUserSession $userSession) {
		$this->commentsManager = $commentsManager;
		$this->userSession = $userSession;
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @param Server $server
	 * @return void
	 */
	function initialize(Server $server) {
		$server->xml->namespaceMap[self::NS_OWNCLOUD] = 'oc';

		$server->protectedProperties[] = self::ID_PROPERTYNAME;

		$server->on('method:POST', [$this, 'httpPost']);

		$this->server = $server;
	}

	/**
	 * POST operation on Comments collections
	 *
	 * @param RequestInterface $request request object
	 * @param ResponseInterface $response response object
	 * @return null|false
	 */
	public function httpPost(RequestInterface $request, ResponseInterface $response) {
		$path = $request->getPath();

		// Making sure the node exists
		try {
			$node = $this->server->tree->getNodeForPath($path);
		} catch (NotFound $e) {
			return null;
		}

		if ($node instanceof EntityCollection) {
			$data = $request->getBodyAsString();

			$comment = $this->createComment($node->getName(), $node->getId(), $data, $request->getHeader('Content-Type'));
			$url = $request->getUrl() . '/' . urlencode($comment->getId());

			$response->setHeader('Location', $url);

			// created
			$response->setStatus(201);
			return false;
		}
	}

	/**
	 * Creates a new comment
	 *
	 * @param string $objectType e.g. "files"
	 * @param string $objectId e.g. the file id
	 * @param string $data JSON encoded string containing the properties of the tag to create
	 * @param string $contentType content type of the data
	 * @return IComment newly created comment
	 *
	 * @throws BadRequest if a field was missing
	 * @throws UnsupportedMediaType if the content type is not supported
	 */
	private function createComment($objectType, $objectId, $data, $contentType = 'application/json') {
		if ($contentType === 'application/json') {
			$data = json_decode($data, true);
		} else {
			throw new UnsupportedMediaType();
		}

		$actorType = $data['actorType'];
		$actorId = null;
		if($actorType === 'users') {
			$user = $this->userSession->getUser();
			if(!is_null($user)) {
				$actorId = $user->getUID();
			}
		}
		if(is_null($actorId)) {
			throw new BadRequest('Invalid actor "' .  $actorType .'"');
		}

		try {
			$comment = $this->commentsManager->create($actorType, $actorId, $objectType, $objectId);
			$properties = [
				'message' => 'setMessage',
				'verb' => 'setVerb',
			];
			foreach($properties as $property => $setter) {
				$comment->$setter($property);
			}
			$this->commentsManager->save($comment);
			return $comment;
		} catch (\InvalidArgumentException $e) {
			throw new BadRequest('Invalid input values', 0, $e);
		}
	}



}
