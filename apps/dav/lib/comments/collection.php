<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

use OCP\Comments\ICommentsManager;
use Sabre\DAV\ICollection;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;

class Collection implements ICollection {

	/** @var EntityTypeCollection[] */
	private $entityTypeCollections = [];

	/** @var ICommentsManager */
	protected $commentsManager;

	/** @var string */
	protected $name = 'comments';

	/**
	 * @param ICommentsManager $commentsManager
	 */
	public function __construct(ICommentsManager $commentsManager) {
		$this->commentsManager = $commentsManager;
		/** @noinspection PhpUndefinedClassInspection */
		$userFolder = \OC::$server->getUserFolder();
		$this->entityTypeCollections['files'] = new EntityTypeCollection('files', $commentsManager, $userFolder);
	}

	/**
	 * Creates a new file in the directory
	 *
	 * @param string $name Name of the file
	 * @param resource|string $data Initial payload
	 * @return null|string
	 * @throws Forbidden
	 */
	function createFile($name, $data = null) {
		throw new Forbidden('Cannot create comments by id');
	}

	/**
	 * Creates a new subdirectory
	 *
	 * @param string $name
	 * @throws Forbidden
	 */
	function createDirectory($name) {
		throw new Forbidden('Permission denied to create collections');
	}

	/**
	 * Returns a specific child node, referenced by its name
	 *
	 * This method must throw Sabre\DAV\Exception\NotFound if the node does not
	 * exist.
	 *
	 * @param string $name
	 * @return \Sabre\DAV\INode
	 * @throws NotFound
	 */
	function getChild($name) {
		if(isset($this->entityTypeCollections[$name])) {
			return $this->entityTypeCollections[$name];
		}
		throw new NotFound('Entity type "' . $name . '" not found."');
	}

	/**
	 * Returns an array with all the child nodes
	 *
	 * @return \Sabre\DAV\INode[]
	 */
	function getChildren() {
		return $this->entityTypeCollections;
	}

	/**
	 * Checks if a child-node with the specified name exists
	 *
	 * @param string $name
	 * @return bool
	 */
	function childExists($name) {
		return $name === 'files';
	}

	/**
	 * Deleted the current node
	 *
	 * @throws Forbidden
	 */
	function delete() {
		throw new Forbidden('Permission denied to delete this collection');
	}

	/**
	 * Returns the name of the node.
	 *
	 * This is used to generate the url.
	 *
	 * @return string
	 */
	function getName() {
		return $this->name;
	}

	/**
	 * Renames the node
	 *
	 * @param string $name The new name
	 * @throws Forbidden
	 */
	function setName($name) {
		throw new Forbidden('Permission denied to rename this collection');
	}

	/**
	 * Returns the last modification time, as a unix timestamp
	 *
	 * @return int
	 */
	function getLastModified() {
		return null;
	}
}
