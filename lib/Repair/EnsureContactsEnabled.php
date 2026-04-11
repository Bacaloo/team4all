<?php

declare(strict_types=1);

namespace OCA\Team4All\Repair;

use OCP\App\IAppManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class EnsureContactsEnabled implements IRepairStep {
	public function __construct(
		private IAppManager $appManager,
	) {
	}

	public function getName(): string {
		return 'Ensure Contacts is enabled before Team4All activation';
	}

	public function run(IOutput $output): void {
		if (!$this->appManager->isEnabledForAnyone('contacts')) {
			throw new \RuntimeException('The Contacts app must be enabled system-wide before Team4All can be activated.');
		}
	}
}
