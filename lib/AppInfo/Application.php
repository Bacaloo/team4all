<?php

declare(strict_types=1);

namespace OCA\Team4All\AppInfo;

use OCP\App\IAppManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
    public const APP_ID = 'team4all';
    private const REQUIRED_APP_ID = 'contacts';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
    }

    public function boot(IBootContext $context): void {
        /** @var IAppManager $appManager */
        $appManager = $context->getServerContainer()->get(IAppManager::class);
        if (!$appManager->isEnabledForAnyone(self::REQUIRED_APP_ID)) {
            if (method_exists($appManager, 'disableApp')) {
                $appManager->disableApp(self::APP_ID);
            }

            throw new \RuntimeException('The Contacts app must be enabled system-wide before Team4All can be used.');
        }
    }
}
