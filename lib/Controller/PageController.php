<?php

declare(strict_types=1);

namespace OCA\Team4All\Controller;

use OCA\Team4All\Service\GroupProvisioningService;
use OCA\Team4All\Service\TeamFolderProvisioningService;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

class PageController extends Controller {
	private const REQUIRED_APPS = [
		'calendar' => 'Calendar',
		'circles' => 'Teams',
		'files' => 'Files',
		'files_pdfviewer' => 'PDF viewer',
		'files_sharing' => 'File sharing',
		'groupfolders' => 'Team folders',
		'mail' => 'Mail',
		'tasks' => 'Tasks',
		'onlyoffice' => 'ONLYOFFICE',
	];

	public function __construct(
		string $appName,
		IRequest $request,
		private IAppManager $appManager,
		private GroupProvisioningService $groupProvisioningService,
		private TeamFolderProvisioningService $teamFolderProvisioningService,
	) {
		parent::__construct($appName, $request);
	}

	#[NoCSRFRequired]
	public function index(): TemplateResponse {
		if (!$this->groupProvisioningService->canCurrentUserAccess()) {
			$response = new TemplateResponse($this->appName, 'access_denied');
			$response->setStatus(Http::STATUS_FORBIDDEN);

			return $response;
		}

		$this->groupProvisioningService->ensureTeam4AllGroup();
		$this->teamFolderProvisioningService->ensureTeamFolder();

		$missingApps = $this->getMissingRequiredApps();
		if ($missingApps !== []) {
			return new TemplateResponse($this->appName, 'contacts_missing', [
				'missingApps' => $missingApps,
			]);
		}

		return new TemplateResponse($this->appName, 'main');
	}

	/**
	 * @return list<array{id: string, name: string}>
	 */
	private function getMissingRequiredApps(): array {
		$missingApps = [];

		foreach (self::REQUIRED_APPS as $appId => $appName) {
			if (!$this->appManager->isEnabledForAnyone($appId)) {
				$missingApps[] = [
					'id' => $appId,
					'name' => $appName,
				];
			}
		}

		return $missingApps;
	}
}
