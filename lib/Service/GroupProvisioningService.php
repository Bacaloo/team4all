<?php

declare(strict_types=1);

namespace OCA\Team4All\Service;

use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;

class GroupProvisioningService {
	public const GROUP_ID = 'Team4All';
	private const ADMIN_GROUP_ID = 'admin';

	public function __construct(
		private IGroupManager $groupManager,
		private IUserSession $userSession,
	) {
	}

	public function canCurrentUserAccess(): bool {
		$user = $this->userSession->getUser();
		if (!$user instanceof IUser) {
			return false;
		}

		$group = $this->groupManager->get(self::GROUP_ID);
		if ($group instanceof IGroup) {
			return $group->inGroup($user);
		}

		return $this->isAdminUser($user);
	}

	public function isCurrentUserAdmin(): bool {
		$user = $this->userSession->getUser();

		return $user instanceof IUser && $this->isAdminUser($user);
	}

	public function groupExists(): bool {
		return $this->groupManager->get(self::GROUP_ID) instanceof IGroup;
	}

	public function getCurrentUser(): ?IUser {
		$user = $this->userSession->getUser();

		return $user instanceof IUser ? $user : null;
	}

	public function ensureTeam4AllGroup(): void {
		$group = $this->groupManager->get(self::GROUP_ID);
		if ($group instanceof IGroup) {
			return;
		}

		$user = $this->userSession->getUser();
		if (!$user instanceof IUser || !$this->isAdminUser($user)) {
			return;
		}

		$group = $this->groupManager->createGroup(self::GROUP_ID);
		if (!$group instanceof IGroup) {
			return;
		}

		if (!$group->inGroup($user)) {
			$group->addUser($user);
		}
	}

	private function isAdminUser(IUser $user): bool {
		$adminGroup = $this->groupManager->get(self::ADMIN_GROUP_ID);

		return $adminGroup instanceof IGroup && $adminGroup->inGroup($user);
	}
}
