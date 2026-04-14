<?php

declare(strict_types=1);

namespace OCA\Team4All\Controller;

use OCA\Team4All\Service\ContactGroupProvisioningService;
use OCA\Team4All\Service\GroupProvisioningService;
use OCA\Team4All\Service\TeamFolderProvisioningService;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
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
		private ContactGroupProvisioningService $contactGroupProvisioningService,
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
		$this->contactGroupProvisioningService->ensureTeam4AllContactGroup();
		$this->teamFolderProvisioningService->ensureTeamFolderForUserContext();

		$missingApps = $this->getMissingRequiredApps();
		if ($missingApps !== []) {
			return new TemplateResponse($this->appName, 'contacts_missing', [
				'missingApps' => $missingApps,
			]);
		}

		$team4AllGroups = $this->contactGroupProvisioningService->getTeam4AllContactGroups();

		return new TemplateResponse($this->appName, 'main', [
			'team4AllGroups' => $team4AllGroups,
			'team4AllGroupCount' => count($team4AllGroups),
		]);
	}

	public function updateNote(string $uri, string $note = ''): JSONResponse {
		if (!$this->groupProvisioningService->canCurrentUserAccess()) {
			return new JSONResponse([
				'saved' => false,
				'message' => 'Access denied.',
			], Http::STATUS_FORBIDDEN);
		}

		$saved = $this->contactGroupProvisioningService->updateContactNoteByUri($uri, $note);

		return new JSONResponse([
			'saved' => $saved,
		], $saved ? Http::STATUS_OK : Http::STATUS_BAD_REQUEST);
	}

	public function fetchContact(string $uri = ''): JSONResponse {
		if (!$this->groupProvisioningService->canCurrentUserAccess()) {
			return new JSONResponse([
				'found' => false,
				'message' => 'Access denied.',
			], Http::STATUS_FORBIDDEN);
		}

		$contact = $this->contactGroupProvisioningService->getContactByUri($uri);
		if ($contact === null) {
			return new JSONResponse([
				'found' => false,
			], Http::STATUS_NOT_FOUND);
		}

		return new JSONResponse([
			'found' => true,
			'contact' => $contact,
		]);
	}

	public function updateContact(
		string $uri,
		string $prefix = '',
		string $firstName = '',
		string $lastName = '',
		string $addressType = 'work',
		string $streetAddress = '',
		string $postalCode = '',
		string $locality = '',
		string $telephones = '',
		string $emails = '',
	): JSONResponse {
		if (!$this->groupProvisioningService->canCurrentUserAccess()) {
			return new JSONResponse([
				'saved' => false,
				'message' => 'Access denied.',
			], Http::STATUS_FORBIDDEN);
		}

		$saved = $this->contactGroupProvisioningService->updateContactDetailsByUri(
			$uri,
			$prefix,
			$firstName,
			$lastName,
			$addressType,
			$streetAddress,
			$postalCode,
			$locality,
			$telephones,
			$emails,
		);

		return new JSONResponse([
			'saved' => $saved,
		], $saved ? Http::STATUS_OK : Http::STATUS_BAD_REQUEST);
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
