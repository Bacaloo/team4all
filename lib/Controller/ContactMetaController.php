<?php

declare(strict_types=1);

namespace OCA\Team4All\Controller;

use OCA\Team4All\Service\ContactMetaService;
use OCA\Team4All\Service\GroupProvisioningService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class ContactMetaController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private ContactMetaService $contactMetaService,
		private GroupProvisioningService $groupProvisioningService,
	) {
		parent::__construct($appName, $request);
	}

	public function show(string $contactUid = ''): JSONResponse {
		if (!$this->groupProvisioningService->canCurrentUserAccess()) {
			return new JSONResponse([
				'found' => false,
				'message' => 'Access denied.',
			], Http::STATUS_FORBIDDEN);
		}

		$currentUser = $this->groupProvisioningService->getCurrentUser();
		if ($contactUid === '' || $currentUser === null) {
			return new JSONResponse([
				'found' => false,
			], Http::STATUS_BAD_REQUEST);
		}

		return new JSONResponse([
			'found' => true,
			'meta' => $this->contactMetaService->getMetaByNcUserIdAndContactUid(
				$currentUser->getUID(),
				$contactUid,
			),
		]);
	}

	public function save(
		string $contactUid,
		?string $anrede = null,
		?string $briefanrede = null,
	): JSONResponse {
		if (!$this->groupProvisioningService->canCurrentUserAccess()) {
			return new JSONResponse([
				'saved' => false,
				'message' => 'Access denied.',
			], Http::STATUS_FORBIDDEN);
		}

		$currentUser = $this->groupProvisioningService->getCurrentUser();
		if ($contactUid === '' || $currentUser === null) {
			return new JSONResponse([
				'saved' => false,
			], Http::STATUS_BAD_REQUEST);
		}

		return new JSONResponse([
			'saved' => true,
			'meta' => $this->contactMetaService->saveMetaByNcUserIdAndContactUid(
				$currentUser->getUID(),
				$contactUid,
				$anrede,
				$briefanrede,
			),
		]);
	}
}
