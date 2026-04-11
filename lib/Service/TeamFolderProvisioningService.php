<?php

declare(strict_types=1);

namespace OCA\Team4All\Service;

use OCP\Constants;
use OCP\IServerContainer;
use Psr\Container\ContainerExceptionInterface;

class TeamFolderProvisioningService {
	public const FOLDER_NAME = 'Team4All';
	private const GROUP_FOLDERS_MANAGER_CLASS = 'OCA\\GroupFolders\\Folder\\FolderManager';

	public function __construct(
		private GroupProvisioningService $groupProvisioningService,
		private IServerContainer $serverContainer,
	) {
	}

	public function ensureTeamFolder(): void {
		if (!$this->groupProvisioningService->isCurrentUserAdmin()) {
			return;
		}

		if (!class_exists(self::GROUP_FOLDERS_MANAGER_CLASS)) {
			return;
		}

		$groupFolderManager = $this->resolveGroupFolderManager();
		if ($groupFolderManager === null) {
			return;
		}

		$mountPoint = $groupFolderManager->trimMountpoint(self::FOLDER_NAME);
		$folderId = $this->findFolderIdByMountPoint($groupFolderManager, $mountPoint);

		if ($folderId === null) {
			$folderId = $groupFolderManager->createFolder($mountPoint);
		}

		$folder = $groupFolderManager->getFolder($folderId);
		$groups = is_object($folder) && property_exists($folder, 'groups') && is_array($folder->groups)
			? $folder->groups
			: [];

		if (!array_key_exists(GroupProvisioningService::GROUP_ID, $groups)) {
			$groupFolderManager->addApplicableGroup($folderId, GroupProvisioningService::GROUP_ID);
		}

		$groupFolderManager->setGroupPermissions(
			$folderId,
			GroupProvisioningService::GROUP_ID,
			Constants::PERMISSION_READ,
		);
	}

	private function resolveGroupFolderManager(): ?object {
		try {
			$manager = $this->serverContainer->get(self::GROUP_FOLDERS_MANAGER_CLASS);
		} catch (ContainerExceptionInterface) {
			return null;
		}

		return is_object($manager) ? $manager : null;
	}

	private function findFolderIdByMountPoint(object $groupFolderManager, string $mountPoint): ?int {
		foreach ($groupFolderManager->getAllFolders() as $folder) {
			if (!is_object($folder) || !property_exists($folder, 'mountPoint') || !property_exists($folder, 'id')) {
				continue;
			}

			if ($folder->mountPoint === $mountPoint) {
				return (int)$folder->id;
			}
		}

		return null;
	}
}
