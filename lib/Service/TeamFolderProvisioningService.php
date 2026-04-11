<?php

declare(strict_types=1);

namespace OCA\Team4All\Service;

use OCP\Constants;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
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
		if (!$this->canProvisionTeamFolder()) {
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

		$documentsFolder = $this->ensureDocumentsFolder($folder, $mountPoint);
		if (!$documentsFolder instanceof Folder) {
			return;
		}

		$ruleManager = $this->resolveGroupFolderRuleManager();
		if ($ruleManager === null) {
			return;
		}

		$teamFolder = $documentsFolder->getParent();
		if (!$teamFolder instanceof Folder) {
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

	private function ensureDocumentsFolder(object $folder, string $mountPoint): ?Folder {
		$currentUser = $this->groupProvisioningService->getCurrentUser();
		if ($currentUser !== null) {
			try {
				/** @var IRootFolder $rootFolder */
				$rootFolder = $this->serverContainer->get(IRootFolder::class);
				$userFolder = $rootFolder->getUserFolder($currentUser->getUID());

				return $userFolder->getOrCreateFolder($mountPoint . '/' . self::DOCUMENTS_FOLDER_NAME);
			} catch (NotFoundException|NotPermittedException) {
				// Fall back to the group-folder node resolution below.
			}
		}

		$teamFolder = $this->resolveTeamFolderNode($folder, $mountPoint);
		if (!$teamFolder instanceof Folder) {
			return null;
		}

		try {
			return $teamFolder->getOrCreateFolder(self::DOCUMENTS_FOLDER_NAME);
		} catch (NotPermittedException) {
			return null;
		}
	}

	private function resolveTeamFolderNode(object $folder, string $mountPoint): ?Folder {
		/** @var IRootFolder $rootFolder */
		$rootFolder = $this->serverContainer->get(IRootFolder::class);

		$rootId = property_exists($folder, 'rootId') ? $folder->rootId : null;
		if (is_int($rootId) || is_numeric($rootId)) {
			$node = $rootFolder->getFirstNodeById((int)$rootId);
			if ($node instanceof Folder) {
				return $node;
			}

			$nodes = $rootFolder->getById((int)$rootId);
			foreach ($nodes as $candidate) {
				if ($candidate instanceof Folder) {
					return $candidate;
				}
			}
		}

		$currentUser = $this->groupProvisioningService->getCurrentUser();
		if ($currentUser === null) {
			return null;
		}

		try {
			$userFolder = $rootFolder->getUserFolder($currentUser->getUID());
			$node = $userFolder->get($mountPoint);
		} catch (NotFoundException|NotPermittedException) {
			return null;
		}

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

	private function canProvisionTeamFolder(): bool {
		if (!$this->groupProvisioningService->groupExists()) {
			return $this->groupProvisioningService->isCurrentUserAdmin();
		}

		return true;
	}
}
