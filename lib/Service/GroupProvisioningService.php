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

	public function getProvisioningUser(): ?IUser {
		$currentUser = $this->getCurrentUser();
		if ($currentUser instanceof IUser) {
			return $currentUser;
		}

		$group = $this->groupManager->get(self::GROUP_ID);
		if ($group instanceof IGroup) {
			$users = $group->getUsers();

			if (isset($users[0]) && $users[0] instanceof IUser) {
				return $users[0];
			}
		}

		$adminGroup = $this->groupManager->get(self::ADMIN_GROUP_ID);
		if (!$adminGroup instanceof IGroup) {
			return null;
		}

		$adminUsers = $adminGroup->getUsers();

		return $adminUsers[0] ?? null;
	}

	/**
	 * @return list<IUser>
	 */
	public function getTeam4AllGroupUsers(): array {
		$group = $this->groupManager->get(self::GROUP_ID);
		if (!$group instanceof IGroup) {
			return [];
		}

		return array_values(array_filter(
			$group->getUsers(),
			static fn(mixed $user): bool => $user instanceof IUser
		));
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
