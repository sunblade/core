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
use OCP\Files\Folder;
use Sabre\DAV\Exception\MethodNotAllowed;

/**
 * Class EntityCollection
 *
 * this represents a specific holder of comments, identified by an entity type
 * (class member $name) and an entity id (class member $id).
 *
 * @package OCA\DAV\Comments
 */
class EntityCollection extends Collection {
	/** @var  Folder */
	protected $fileRoot;

	/** @var  string */
	protected $id;

	/**
	 * @param string $id
	 * @param string $name
	 * @param ICommentsManager $commentsManager
	 * @param Folder $fileRoot
	 */
	public function __construct($id, $name, ICommentsManager $commentsManager, Folder $fileRoot) {
		foreach(['id', 'name'] as $property) {
			$$property = trim($$property);
			if(empty($$property) || !is_string($$property)) {
				throw new \InvalidArgumentException('"' . $property . '" parameter must be non-empty string');
			}
		}
		$this->id = $id;
		$this->name = $name;
		$this->commentsManager = $commentsManager;
		$this->fileRoot = $fileRoot;
	}

	/**
	 * returns the ID of this entity
	 *
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Returns a specific child node, referenced by its name
	 *
	 * This method must throw Sabre\DAV\Exception\NotFound if the node does not
	 * exist.
	 *
	 * @param string $name
	 * @return \Sabre\DAV\INode
	 */
	function getChild($name) {
		// TODO: return comment by id (referred to by $name)
	}

	/**
	 * Returns an array with all the child nodes
	 *
	 * @return \Sabre\DAV\INode[]
	 * @throws MethodNotAllowed
	 */
	function getChildren() {
		// TODO: if necessary, return  all Comments belonging to this entity
	}

	/**
	 * Checks if a child-node with the specified name exists
	 *
	 * @param string $name
	 * @return bool
	 */
	function childExists($name) {

	}


}

