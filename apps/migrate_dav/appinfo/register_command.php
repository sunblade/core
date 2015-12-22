<?php

use OCA\Migrate_Dav\Command\MigrateAddressbooks;

$config = \OC::$server->getConfig();
$dbConnection = \OC::$server->getDatabaseConnection();
$userManager = OC::$server->getUserManager();
$config = \OC::$server->getConfig();
$logger = \OC::$server->getLogger();

/** @var Symfony\Component\Console\Application $application */
$application->add(new MigrateAddressbooks($userManager, $dbConnection, $config, $logger));
