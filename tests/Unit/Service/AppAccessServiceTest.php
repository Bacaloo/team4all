<?php

declare(strict_types=1);

namespace OCA\Team4All\Tests\Unit\Service;

use OCA\Team4All\Service\AppAccessService;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

class AppAccessServiceTest extends TestCase {
	public function testAccessIsDeniedWithoutLoggedInUser(): void {
		$userSession = $this->createMock(IUserSession::class);
		$userSession->expects(self::once())
			->method('getUser')
			->willReturn(null);

		$service = new AppAccessService($userSession);

		self::assertFalse($service->canCurrentUserAccess());
		self::assertNull($service->getCurrentUser());
	}

	public function testAccessIsAllowedForLoggedInUserByDefault(): void {
		$user = $this->createMock(IUser::class);

		$userSession = $this->createMock(IUserSession::class);
		$userSession->expects(self::exactly(2))
			->method('getUser')
			->willReturn($user);

		$service = new AppAccessService($userSession);

		self::assertTrue($service->canCurrentUserAccess());
		self::assertSame($user, $service->getCurrentUser());
	}
}
