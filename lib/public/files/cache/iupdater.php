<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>>
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

namespace OCP\Files\Cache;
use OCP\Files\Storage;

/**
 * Update the cache and propagate changes
 *
 */
interface IUpdater {
	/**
	 * Get the propagator for etags and mtime for the view the updater works on
	 *
	 * @return IPropagator
	 */
	public function getPropagator();

	/**
	 * Propagate etag and mtime changes for the parent folders of $path up to the root of the filesystem
	 *
	 * @param string $path the path of the file to propagate the changes for
	 * @param int|null $time the timestamp to set as mtime for the parent folders, if left out the current time is used
	 */
	public function propagate($path, $time = null);

	/**
	 * Update the cache for $path and update the size, etag and mtime of the parent folders
	 *
	 * @param string $path
	 * @param int $time
	 */
	public function update($path, $time = null);

	/**
	 * Remove $path from the cache and update the size, etag and mtime of the parent folders
	 *
	 * @param string $path
	 */
	public function remove($path);

	/**
	 * Rename a file or folder in the cache and update the size, etag and mtime of the parent folders
	 *
	 * @param \OCP\Files\Storage $sourceStorage
	 * @param string $source
	 * @param string $target
	 */
	public function renameFromStorage(Storage $sourceStorage, $source, $target);
}
