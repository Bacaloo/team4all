<?php

declare(strict_types=1);

namespace OCA\Team4All\AppInfo;

use OCA\Team4All\Service\GroupProvisioningService;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\INavigationManager;
use OCP\IURLGenerator;

class Application extends App implements IBootstrap {
	public const APP_ID = 'team4all';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
	}

	public function boot(IBootContext $context): void {
		$context->injectFn([$this, 'registerNavigation']);
	}

	public function registerNavigation(
		INavigationManager $navigationManager,
		IURLGenerator $urlGenerator,
		GroupProvisioningService $groupProvisioningService,
	): void {
		if (!$groupProvisioningService->canCurrentUserAccess()) {
			return;
		}

		$navigationManager->add(static function () use ($urlGenerator): array {
			return [
				'id' => self::APP_ID,
				'order' => 80,
				'href' => $urlGenerator->linkToRoute('team4all.page.index'),
				'icon' => $urlGenerator->getAbsoluteURL('/apps/' . self::APP_ID . '/img/app.svg'),
				'name' => 'Team4All',
			];
		});
	}
}
