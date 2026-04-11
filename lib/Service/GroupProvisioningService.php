<?php

declare(strict_types=1);

namespace OCA\Team4All\Service;

use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;

class GroupProvisioningService {
	public const GROUP_ID = 'Team4All';

	public function __construct(
		private IGroupManager $groupManager,
		private IUserSession $userSession,
	) {
	}

	public function ensureTeam4AllGroup(): void {
		$group = $this->groupManager->get(self::GROUP_ID);
		if ($group instanceof IGroup) {
			return;
		}

		$user = $this->userSession->getUser();
		if (!$user instanceof IUser) {
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
}
