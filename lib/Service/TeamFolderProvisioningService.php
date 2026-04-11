<?php

declare(strict_types=1);

namespace OCA\Team4All\Service;

use OCP\Constants;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IServerContainer;
use Psr\Container\ContainerExceptionInterface;

class TeamFolderProvisioningService {
	public const FOLDER_NAME = 'Team4All';
	public const DOCUMENTS_FOLDER_NAME = 'Dokumente';
	private const GROUP_FOLDERS_MANAGER_CLASS = 'OCA\\GroupFolders\\Folder\\FolderManager';
	private const GROUP_FOLDERS_RULE_CLASS = 'OCA\\GroupFolders\\ACL\\Rule';
	private const GROUP_FOLDERS_RULE_MANAGER_CLASS = 'OCA\\GroupFolders\\ACL\\RuleManager';
	private const GROUP_FOLDERS_USER_MAPPING_CLASS = 'OCA\\GroupFolders\\ACL\\UserMapping\\UserMapping';
	private const ROOT_DENY_MASK = Constants::PERMISSION_UPDATE
		| Constants::PERMISSION_CREATE
		| Constants::PERMISSION_DELETE
		| Constants::PERMISSION_SHARE;
	private const DOCUMENTS_ALLOW_MASK = Constants::PERMISSION_ALL;

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
			Constants::PERMISSION_ALL,
		);

		$groupFolderManager->setFolderACL($folderId, true);

		$teamFolder = $this->resolveTeamFolderNode($folder->rootId ?? null);
		if (!$teamFolder instanceof Folder) {
			return;
		}

		$documentsFolder = $teamFolder->getOrCreateFolder(self::DOCUMENTS_FOLDER_NAME);
		$ruleManager = $this->resolveGroupFolderRuleManager();
		if ($ruleManager === null) {
			return;
		}

		$this->saveRule(
			$ruleManager,
			(int)$teamFolder->getId(),
			self::ROOT_DENY_MASK,
			0,
		);

		$this->saveRule(
			$ruleManager,
			(int)$documentsFolder->getId(),
			self::DOCUMENTS_ALLOW_MASK,
			self::DOCUMENTS_ALLOW_MASK,
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

	private function resolveTeamFolderNode(mixed $rootId): ?Folder {
		if (!is_int($rootId) && !is_numeric($rootId)) {
			return null;
		}

		/** @var IRootFolder $rootFolder */
		$rootFolder = $this->serverContainer->get(IRootFolder::class);
		$node = $rootFolder->getFirstNodeById((int)$rootId);

		return $node instanceof Folder ? $node : null;
	}

	private function resolveGroupFolderRuleManager(): ?object {
		if (!class_exists(self::GROUP_FOLDERS_RULE_MANAGER_CLASS)) {
			return null;
		}

		try {
			$manager = $this->serverContainer->get(self::GROUP_FOLDERS_RULE_MANAGER_CLASS);
		} catch (ContainerExceptionInterface) {
			return null;
		}

		return is_object($manager) ? $manager : null;
	}

	private function saveRule(object $ruleManager, int $fileId, int $mask, int $permissions): void {
		if (!class_exists(self::GROUP_FOLDERS_RULE_CLASS) || !class_exists(self::GROUP_FOLDERS_USER_MAPPING_CLASS)) {
			return;
		}

		$userMapping = new (self::GROUP_FOLDERS_USER_MAPPING_CLASS)(
			'group',
			GroupProvisioningService::GROUP_ID,
			GroupProvisioningService::GROUP_ID,
		);

		$rule = new (self::GROUP_FOLDERS_RULE_CLASS)(
			$userMapping,
			$fileId,
			$mask,
			$permissions,
		);

		$ruleManager->saveRule($rule);
	}
}
