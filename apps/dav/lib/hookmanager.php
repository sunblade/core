<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
namespace OCA\DAV;

use OCA\DAV\CardDAV\SyncService;
use OCP\IUserManager;
use OCP\Util;

class HookManager {

	/** @var IUserManager */
	private $userManager;

	/** @var SyncService */
	private $syncService;

	public function __construct(IUserManager $userManager, SyncService $syncService) {
		$this->userManager = $userManager;
		$this->syncService = $syncService;
	}

	public function setup() {
		Util::connectHook('OC_User',
			'post_createUser',
			$this,
			'postCreateUser');
		Util::connectHook('OC_User',
			'post_deleteUser',
			$this,
			'postDeleteUser');
	}

	public function postCreateUser($params) {
		$user = $this->userManager->get($params['uid']);
		$this->syncService->updateUser($user);
	}

	public function postDeleteUser($params) {
		$this->syncService->deleteUser($params['uid']);

	}

}
