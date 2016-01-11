<?php

namespace OCA\DAV\Command;

use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\Connector\Sabre\Principal;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateAddressBook extends Command {

	/** @var IUserManager */
	protected $userManager;

	/** @var \OCP\IDBConnection */
	protected $dbConnection;

	/** @var IConfig */
	private $config;

	/** @var ILogger  */
	private $logger;

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
				->setName('dav:create-addressbook')
				->setDescription('Create a dav addressbook')
				->addArgument('user',
						InputArgument::REQUIRED,
						'User for whom the addressbook will be created')
				->addArgument('name',
						InputArgument::REQUIRED,
						'Name of the addressbook');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$user = $input->getArgument('user');
		if (!$this->userManager->userExists($user)) {
			throw new \InvalidArgumentException("User <$user> in unknown.");
		}
		$principalBackend = new Principal(
				$this->config,
				$this->userManager
		);

		$name = $input->getArgument('name');
		$carddav = new CardDavBackend($this->dbConnection, $principalBackend, $this->logger);
		$carddav->createAddressBook("principals/users/$user", $name, []);
	}
}
