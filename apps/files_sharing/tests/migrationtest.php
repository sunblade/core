<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
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


use OCA\Files_Sharing\Tests\TestCase;
use OCA\Files_Sharing\Migration;

/**
 * Class MigrationTest
 *
 * @group DB
 */
class MigrationTest extends TestCase {

	/** @var \OCP\IDBConnection */
	private $connection;

	/** @var Migration */
	private $migration;

	private $table = 'share';

	public function setUp() {
		parent::setUp();

		$this->connection = \OC::$server->getDatabaseConnection();
		$this->migration = new Migration($this->connection);

		$this->cleanDB();
	}

	public function tearDown() {
		parent::tearDown();
		$this->cleanDB();
	}

	private function cleanDB() {
		$query = $this->connection->getQueryBuilder();
		$query->delete($this->table)->execute();
	}

	public function addDummyValues() {
		$query = $this->connection->getQueryBuilder();
		$query->insert($this->table)
			->values(
				array(
					'share_type' => $query->createParameter('share_type'),
					'share_with' => $query->createParameter('share_with'),
					'uid_owner' => $query->createParameter('uid_owner'),
					'uid_initiator' => $query->createParameter('uid_initiator'),
					'parent' => $query->createParameter('parent'),
					'item_type' => $query->createParameter('item_type'),
					'item_source' => $query->createParameter('item_source'),
					'item_target' => $query->createParameter('item_target'),
					'file_source' => $query->createParameter('file_source'),
					'file_target' => $query->createParameter('file_target'),
					'permissions' => $query->createParameter('permissions'),
					'stime' => $query->createParameter('stime'),
				)
			);
		// shared contact, shouldn't be modified
		$query->setParameter('share_type', \OCP\Share::SHARE_TYPE_CONTACT)
			->setParameter('share_with', 'user1')
			->setParameter('uid_owner', 'owner1')
			->setParameter('uid_initiator', '')
			->setParameter('parent', null)
			->setParameter('item_type', 'contact')
			->setParameter('item_source', '2')
			->setParameter('item_target', '/2')
			->setParameter('file_source', null)
			->setParameter('file_target', null)
			->setParameter('permissions', 31)
			->setParameter('stime', time());
		$this->assertSame(1,
			$query->execute()
		);
		// shared calendar, shouldn't be modified
		$query->setParameter('share_type', \OCP\Share::SHARE_TYPE_USER)
			->setParameter('share_with', 'user1')
			->setParameter('uid_owner', 'owner1')
			->setParameter('uid_initiator', '')
			->setParameter('parent', null)
			->setParameter('item_type', 'calendar')
			->setParameter('item_source', '2')
			->setParameter('item_target', '/2')
			->setParameter('file_source', null)
			->setParameter('file_target', null)
			->setParameter('permissions', 31)
			->setParameter('stime', time());
		$this->assertSame(1,
			$query->execute()
		);
		// single user share, shouldn't be modified
		$query->setParameter('share_type', \OCP\Share::SHARE_TYPE_USER)
			->setParameter('share_with', 'user1')
			->setParameter('uid_owner', 'owner1')
			->setParameter('uid_initiator', '')
			->setParameter('parent', null)
			->setParameter('item_type', 'file')
			->setParameter('item_source', '2')
			->setParameter('item_target', '/2')
			->setParameter('file_source', 2)
			->setParameter('file_target', '/foo')
			->setParameter('permissions', 31)
			->setParameter('stime', time());
		$this->assertSame(1,
			$query->execute()
		);
		// single group share, shouldn't be modified
		$query->setParameter('share_type', \OCP\Share::SHARE_TYPE_GROUP)
			->setParameter('share_with', 'group1')
			->setParameter('uid_owner', 'owner1')
			->setParameter('uid_initiator', '')
			->setParameter('parent', null)
			->setParameter('item_type', 'file')
			->setParameter('item_source', '2')
			->setParameter('item_target', '/2')
			->setParameter('file_source', 2)
			->setParameter('file_target', '/foo')
			->setParameter('permissions', 31)
			->setParameter('stime', time());
		$this->assertSame(1,
			$query->execute()
		);
		$parent = $this->connection->lastInsertId($this->table);
		// unique target for group share, shouldn't be modified
		$query->setParameter('share_type', 2)
			->setParameter('share_with', 'group1')
			->setParameter('uid_owner', 'owner1')
			->setParameter('uid_initiator', '')
			->setParameter('parent', $parent)
			->setParameter('item_type', 'file')
			->setParameter('item_source', '2')
			->setParameter('item_target', '/2')
			->setParameter('file_source', 2)
			->setParameter('file_target', '/foo renamed')
			->setParameter('permissions', 31)
			->setParameter('stime', time());
		$this->assertSame(1,
			$query->execute()
		);
		// first user share, shouldn't be modified
		$query->setParameter('share_type', \OCP\Share::SHARE_TYPE_USER)
			->setParameter('share_with', 'user1')
			->setParameter('uid_owner', 'owner2')
			->setParameter('uid_initiator', '')
			->setParameter('parent', null)
			->setParameter('item_type', 'file')
			->setParameter('item_source', '2')
			->setParameter('item_target', '/2')
			->setParameter('file_source', 2)
			->setParameter('file_target', '/foobar')
			->setParameter('permissions', 31)
			->setParameter('stime', time());
		$this->assertSame(1,
			$query->execute()
		);
		$parent = $this->connection->lastInsertId($this->table);
		// first re-share, should be attached to the first user share after migration
		$query->setParameter('share_type', \OCP\Share::SHARE_TYPE_USER)
			->setParameter('share_with', 'user2')
			->setParameter('uid_owner', 'user1')
			->setParameter('uid_initiator', '')
			->setParameter('parent', $parent)
			->setParameter('item_type', 'file')
			->setParameter('item_source', '2')
			->setParameter('item_target', '/2')
			->setParameter('file_source', 2)
			->setParameter('file_target', '/foobar')
			->setParameter('permissions', 31)
			->setParameter('stime', time());
		$this->assertSame(1,
			$query->execute()
		);
		$parent = $this->connection->lastInsertId($this->table);
		// second re-share, should be attached to the first user share after migration
		$query->setParameter('share_type', \OCP\Share::SHARE_TYPE_USER)
			->setParameter('share_with', 'user3')
			->setParameter('uid_owner', 'user2')
			->setParameter('uid_initiator', '')
			->setParameter('parent', $parent)
			->setParameter('item_type', 'file')
			->setParameter('item_source', '2')
			->setParameter('item_target', '/2')
			->setParameter('file_source', 2)
			->setParameter('file_target', '/foobar')
			->setParameter('permissions', 31)
			->setParameter('stime', time());
		$this->assertSame(1,
			$query->execute()
		);
	}

	public function testRemoveReShares() {
		$this->addDummyValues();
		$this->migration->removeReShares();
		$this->verifyResult();
	}

	public function verifyResult() {
		$query = $this->connection->getQueryBuilder();
		$query->select('*')->from($this->table)->orderBy('id');
		$result = $query->execute()->fetchAll();
		$this->assertSame(8, count($result));

		// shares which shouldn't be modified
		for ($i = 0; $i < 4; $i++) {
			$this->assertSame('owner1', $result[$i]['uid_owner']);
			$this->assertEmpty($result[$i]['uid_initiator']);
			$this->assertNull($result[$i]['parent']);
		}
		// group share with unique target
		$this->assertSame('owner1', $result[4]['uid_owner']);
		$this->assertEmpty($result[4]['uid_initiator']);
		$this->assertNotEmpty($result[4]['parent']);
		// initial user share which was re-shared
		$this->assertSame('owner2', $result[5]['uid_owner']);
		$this->assertSame('', $result[5]['uid_initiator']);
		$this->assertNull($result[5]['parent']);
		// flatted re-shares
		for($i = 6; $i < 8; $i++) {
			$this->assertSame('owner2', $result[$i]['uid_owner']);
			$user = 'user' . ($i - 5);
			$this->assertSame($user, $result[$i]['uid_initiator']);
			$this->assertNull($result[$i]['parent']);
		}
	}

}
