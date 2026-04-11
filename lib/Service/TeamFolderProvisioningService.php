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
use Psr\Log\LoggerInterface;
use Throwable;

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
		private LoggerInterface $logger,
	) {
	}

	public function ensureTeamFolder(): void {
		try {
			if (!$this->canProvisionTeamFolder()) {
				$this->logger->info('Skipped Team4All folder provisioning because current context is not allowed to provision.');
				return;
			}

			if (!class_exists(self::GROUP_FOLDERS_MANAGER_CLASS)) {
				$this->logger->warning('Skipped Team4All folder provisioning because Group Folders is not available.');
				return;
			}

			$groupFolderManager = $this->resolveGroupFolderManager();
			if ($groupFolderManager === null) {
				$this->logger->warning('Skipped Team4All folder provisioning because the Group Folders manager could not be resolved.');
				return;
			}

			$mountPoint = $groupFolderManager->trimMountpoint(self::FOLDER_NAME);
			$folderId = $this->findFolderIdByMountPoint($groupFolderManager, $mountPoint);

			if ($folderId === null) {
				$folderId = $groupFolderManager->createFolder($mountPoint);
				$this->logger->info('Created Team4All team folder.', [
					'mountPoint' => $mountPoint,
					'folderId' => $folderId,
				]);
			}

			$folder = $groupFolderManager->getFolder($folderId);
			$groups = is_object($folder) && property_exists($folder, 'groups') && is_array($folder->groups)
				? $folder->groups
				: [];

			if (!array_key_exists(GroupProvisioningService::GROUP_ID, $groups)) {
				$groupFolderManager->addApplicableGroup($folderId, GroupProvisioningService::GROUP_ID);
				$this->logger->info('Granted Team4All group access to team folder.', [
					'folderId' => $folderId,
					'groupId' => GroupProvisioningService::GROUP_ID,
				]);
			}

			$groupFolderManager->setGroupPermissions(
				$folderId,
				GroupProvisioningService::GROUP_ID,
				Constants::PERMISSION_ALL,
			);

			$groupFolderManager->setFolderACL($folderId, true);

			$documentsFolder = $this->ensureDocumentsFolder($folder, $mountPoint);
			if (!$documentsFolder instanceof Folder) {
				$this->logger->warning('Could not resolve or create the Dokumente folder inside Team4All.', [
					'mountPoint' => $mountPoint,
					'folderId' => $folderId,
				]);
				return;
			}

			$ruleManager = $this->resolveGroupFolderRuleManager();
			if ($ruleManager === null) {
				$this->logger->warning('Skipped ACL provisioning because the Group Folders rule manager could not be resolved.');
				return;
			}

			$teamFolder = $documentsFolder->getParent();
			if (!$teamFolder instanceof Folder) {
				$this->logger->warning('Could not resolve Team4All parent folder from Dokumente node.', [
					'documentsFolderId' => $documentsFolder->getId(),
				]);
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

			$this->logger->info('Ensured Team4All folder structure and permissions.', [
				'teamFolderId' => $teamFolder->getId(),
				'documentsFolderId' => $documentsFolder->getId(),
			]);
		} catch (Throwable $exception) {
			$this->logger->error('Failed to ensure Team4All folder structure.', [
				'exception' => $exception,
			]);
		}
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
		$provisioningUser = $this->groupProvisioningService->getProvisioningUser();
		if ($provisioningUser !== null) {
			try {
				/** @var IRootFolder $rootFolder */
				$rootFolder = $this->serverContainer->get(IRootFolder::class);
				$userFolder = $rootFolder->getUserFolder($provisioningUser->getUID());

				return $userFolder->getOrCreateFolder($mountPoint . '/' . self::DOCUMENTS_FOLDER_NAME);
			} catch (NotFoundException|NotPermittedException) {
				$this->logger->warning('Could not create Dokumente through user mount, falling back to direct folder node.', [
					'uid' => $provisioningUser->getUID(),
					'mountPoint' => $mountPoint,
				]);
			}
		}

		$teamFolder = $this->resolveTeamFolderNode($folder, $mountPoint);
		if (!$teamFolder instanceof Folder) {
			return null;
		}

		try {
			return $teamFolder->getOrCreateFolder(self::DOCUMENTS_FOLDER_NAME);
		} catch (NotPermittedException) {
			$this->logger->warning('Could not create Dokumente through direct Team4All folder node.', [
				'mountPoint' => $mountPoint,
			]);
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

		$provisioningUser = $this->groupProvisioningService->getProvisioningUser();
		if ($provisioningUser === null) {
			return null;
		}

		try {
			$userFolder = $rootFolder->getUserFolder($provisioningUser->getUID());
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
