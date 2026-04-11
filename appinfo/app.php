<?php

declare(strict_types=1);

namespace OCA\Team4All\AppInfo;

use OCP\App\IAppManager;
use OCP\AppFramework\App;

$app = new App('team4all');

/** @var IAppManager $appManager */
$appManager = \OC::$server->get(IAppManager::class);
if (!$appManager->isEnabledForUser('contacts')) {
	throw new \RuntimeException('The Contacts app must be enabled before Team4All can be activated.');
}
