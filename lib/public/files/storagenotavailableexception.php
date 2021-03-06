<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Jesús Macias <jmacias@solidgear.es>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
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

/**
 * Public interface of ownCloud for apps to use.
 * Files/AlreadyExistsException class
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP\Files;
use OC\HintException;

/**
 * Storage is temporarily not available
 * @since 6.0.0
 * @changed 8.2.1 based on HintException
 */
class StorageNotAvailableException extends HintException {

	const STATUS_SUCCESS = 0;
	const STATUS_ERROR = 1;
	const STATUS_INDETERMINATE = 2;
	const STATUS_INCOMPLETE_CONF = 3;
	const STATUS_UNAUTHORIZED = 4;
	const STATUS_TIMEOUT = 5;
	const STATUS_NETWORK_ERROR = 6;

	/**
	 * StorageNotAvailableException constructor.
	 *
	 * @param string $message
	 * @param int $code
	 * @param \Exception $previous
	 * @since 6.0.0
	 */
	public function __construct($message = '', $code = 0, \Exception $previous = null) {
		$l = \OC::$server->getL10N('core');
		parent::__construct($message, $l->t('Storage not available'), $code, $previous);
	}
}
