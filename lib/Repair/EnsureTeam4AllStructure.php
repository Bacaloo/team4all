<?php

declare(strict_types=1);

namespace OCA\Team4All\Repair;

use OCA\Team4All\Service\TeamFolderProvisioningService;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class EnsureTeam4AllStructure implements IRepairStep {
	public function __construct(
		private TeamFolderProvisioningService $teamFolderProvisioningService,
	) {
	}

	public function getName(): string {
		return 'Ensure Team4All folders and permissions';
	}

	public function run(IOutput $output): void {
		$this->teamFolderProvisioningService->ensureTeamFolder();
	}
}
