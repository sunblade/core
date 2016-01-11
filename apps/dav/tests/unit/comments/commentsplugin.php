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

namespace OCA\DAV\Tests\Unit\Comments;

use OC\Comments\Comment;
use OCA\DAV\Comments\CommentsPlugin as CommentsPluginImplementation;
use Sabre\DAV\Server as DAVServer;

class CommentsPlugin extends \Test\TestCase {
	/** @var \Sabre\DAV\Server */
	private $server;

	/** @var \Sabre\DAV\Tree */
	private $tree;

	/** @var \OCP\Comments\ICommentsManager */
	private $commentsManager;

	/** @var  \OCP\IUserSession */
	private $userSession;

	/** @var CommentsPluginImplementation */
	private $plugin;

	public function setUp() {
		parent::setUp();
		$this->tree = $this->getMockBuilder('\Sabre\DAV\Tree')
			->disableOriginalConstructor()
			->getMock();

		$this->server = new DAVServer($this->tree);

		$this->commentsManager = $this->getMock('\OCP\Comments\ICommentsManager');
		$this->userSession = $this->getMock('\OCP\IUserSession');

		$this->plugin = new CommentsPluginImplementation($this->commentsManager, $this->userSession);
		$this->plugin->initialize($this->server);
	}

	public function testCreateComment() {
		$commentData = [
			'actorType' => 'users',
			'verb' => 'comment',
			'message' => 'my first comment',
		];

		$comment = new Comment([
			'objectType' => 'files',
			'objectId' => '42',
			'actorType' => 'users',
			'actorId' => 'alice'
		] + $commentData);
		$comment->setId('23');

		$requestData = json_encode($commentData);

		$user = $this->getMock('OCP\IUser');
		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('alice'));

		$node = $this->getMockBuilder('\OCA\DAV\Comments\EntityCollection')
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->once())
			->method('getName')
			->will($this->returnValue('files'));
		$node->expects($this->once())
			->method('getId')
			->will($this->returnValue('42'));

		$this->commentsManager->expects($this->once())
			->method('create')
			->with('users', 'alice', 'files', '42')
			->will($this->returnValue($comment));

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));

		// technically, this is a shortcut. Inbetween EntityTypeCollection would
		// be returned, but doing it exactly right would not be really
		// unit-testing like, as it would require to haul in a lot of other
		// things.
		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/comments/files/42')
			->will($this->returnValue($node));

		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();

		$response = $this->getMockBuilder('Sabre\HTTP\ResponseInterface')
			->disableOriginalConstructor()
			->getMock();

		$request->expects($this->once())
			->method('getPath')
			->will($this->returnValue('/comments/files/42'));

		$request->expects($this->once())
			->method('getBodyAsString')
			->will($this->returnValue($requestData));

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->will($this->returnValue('application/json'));

		$request->expects($this->once())
			->method('getUrl')
			->will($this->returnValue('http://example.com/dav/comments/files/42'));

		$response->expects($this->once())
			->method('setHeader')
			->with('Location', 'http://example.com/dav/comments/files/42/23');

		$this->plugin->httpPost($request, $response);
	}

}
