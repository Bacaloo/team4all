<?php

declare(strict_types=1);

namespace OCA\Team4All\Service;

use OCP\IUser;
use OCP\IUserSession;

class AppAccessService {
	public const SCOPE_APP = 'app';

	public function __construct(
		private IUserSession $userSession,
	) {
	}

	public function canCurrentUserAccess(string $scope = self::SCOPE_APP): bool {
		$user = $this->getCurrentUser();
		if (!$user instanceof IUser) {
			return false;
		}

		return $this->passesAdditionalRestrictions($scope, $user);
	}

	public function getCurrentUser(): ?IUser {
		$user = $this->userSession->getUser();

		return $user instanceof IUser ? $user : null;
	}

	protected function passesAdditionalRestrictions(string $scope, IUser $user): bool {
		return true;
	}
}
