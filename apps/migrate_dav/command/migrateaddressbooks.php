<?php

namespace OCA\Migrate_Dav\Command;

use OCA\DAV\CardDAV\AddressBook;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\Connector\Sabre\Principal;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;
use Sabre\CardDAV\Plugin;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateAddressbooks extends Command {

	/** @var IUserManager */
	protected $userManager;

	/** @var \OCP\IDBConnection */
	protected $dbConnection;

	/** @var IConfig */
	private $config;

	/** @var ILogger  */
	private $logger;

	/** @var CardDavBackend */
	private $carddav;

	/**
	 * @param IUserManager $userManager
	 * @param IDBConnection $dbConnection
	 * @param IConfig $config
	 * @param ILogger $logger
	 */
	function __construct(IUserManager $userManager,
						 IDBConnection $dbConnection,
						 IConfig $config,
						 ILogger $logger
	) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->dbConnection = $dbConnection;
		$this->config = $config;
		$this->logger = $logger;
	}

	protected function configure() {
		$this
				->setName('migrate:addressbooks')
				->setDescription('Migrate addressbooks from the contacts app to core')
				->addArgument('user',
						InputArgument::OPTIONAL,
						'User for whom all addressbooks will be migrated');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->verifyPreconditions();
		$this->setup();

		if ($input->hasArgument('user')) {
			$user = $input->getArgument('user');
			if (!$this->userManager->userExists($user)) {
				throw new \InvalidArgumentException("User <$user> in unknown.");
			}
			$this->migrateForUser($user);
		}
		$progressBar = new ProgressBar($output);
		$progressBar->start();
		$this->userManager->callForAllUsers(function($user) use ($progressBar) {
			$progressBar->advance();
			/** @var IUser $user*/
			$this->migrateForUser($user->getUID());

		});
		$progressBar->finish();
	}

	private function verifyPreconditions() {
		if (!$this->dbConnection->tableExists('contacts_addressbooks')) {
			throw new \DomainException('Contacts tables are missing. Nothing to do.');
		}
	}

	/**
	 * @param string $user
	 */
	private function migrateForUser($user) {
		// get all addressbooks of that user
		$query = $this->dbConnection->getQueryBuilder();
		$books = $query->select()->from('contacts_addressbooks')
			->where($query->expr()->eq('user', $query->createNamedParameter($user)))
			->execute()
			->fetchAll();

		$principal = "principals/users/$user";
		foreach($books as $book) {

			$knownBooks = $this->carddav->getAddressBooksByUri($principal, $book['uri']);
			if (!is_null($knownBooks)) {
				continue;
			}

			$newId = $this->carddav->createAddressBook($principal, $book['uri'], [
				'{DAV:}displayname' => $book['displayname'],
				'{' . Plugin::NS_CARDDAV . '}addressbook-description' => $book['description']
			]);

			$this->migrateBook($book['id'], $newId);
			$this->migrateShares($book['id'], $newId);
		}
	}

	private function setup() {
		$principalBackend = new Principal(
				$this->config,
				$this->userManager
		);

		$this->carddav = new CardDavBackend($this->dbConnection, $principalBackend, $this->logger);
	}

	/**
	 * @param int $addressBookId
	 * @param int $newAddressBookId
	 */
	private function migrateBook($addressBookId, $newAddressBookId) {
		$query = $this->dbConnection->getQueryBuilder();
		$cards = $query->select()->from('contacts_cards')
			->where($query->expr()->eq('addressbookid', $query->createNamedParameter($addressBookId)))
			->execute()
			->fetchAll();

		foreach ($cards as $card) {
			$this->carddav->createCard($newAddressBookId, $card['uri'], $card['carddata']);
		}
	}

	/**
	 * @param int $addressBookId
	 * @param int $newAddressBookId
	 */
	private function migrateShares($addressBookId, $newAddressBookId) {
		$query = $this->dbConnection->getQueryBuilder();
		$shares = $query->select()->from('share')
			->where($query->expr()->eq('item_source', $query->createNamedParameter($addressBookId)))
			->andWhere($query->expr()->eq('item_type', $query->expr()->literal('addressbook')))
			->andWhere($query->expr()->in('share_type', [ $query->expr()->literal(0), $query->expr()->literal(1)]))
			->execute()
			->fetchAll();

		$add = array_map(function($s) {
			$prefix = 'principal:principals/users/';
			if ($s['share_type'] === 1) {
				$prefix = 'principal:principals/groups/';
			}
			return [
				'href' => $prefix . $s['share_with']
				];
		}, $shares);

		$newAddressBook = $this->carddav->getAddressBookById($newAddressBookId);
		$book = new AddressBook($this->carddav, $newAddressBook);
		$this->carddav->updateShares($book, $add, []);
	}
}
