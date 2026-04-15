<?php

declare(strict_types=1);

namespace OCA\Team4All\Controller;

use OCA\Team4All\Service\AppAccessService;
use OCA\Team4All\Service\ContactMetaService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class ContactMetaController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private AppAccessService $appAccessService,
		private ContactMetaService $contactMetaService,
	) {
		parent::__construct($appName, $request);
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function show(string $contactUid = ''): JSONResponse {
		if (!$this->appAccessService->canCurrentUserAccess()) {
			return new JSONResponse([
				'found' => false,
				'message' => 'Access denied.',
			], Http::STATUS_FORBIDDEN);
		}

		if ($contactUid === '') {
			return new JSONResponse([
				'found' => false,
			], Http::STATUS_BAD_REQUEST);
		}

		return new JSONResponse([
			'found' => true,
			'meta' => $this->contactMetaService->getMetaByContactUid($contactUid),
		]);
	}

	#[NoAdminRequired]
	public function save(
		string $contactUid,
		?string $anrede = null,
		?string $briefanrede = null,
	): JSONResponse {
		if (!$this->appAccessService->canCurrentUserAccess()) {
			return new JSONResponse([
				'saved' => false,
				'message' => 'Access denied.',
			], Http::STATUS_FORBIDDEN);
		}

		if ($contactUid === '') {
			return new JSONResponse([
				'saved' => false,
			], Http::STATUS_BAD_REQUEST);
		}

		return new JSONResponse([
			'saved' => true,
			'meta' => $this->contactMetaService->saveMetaByContactUid($contactUid, $anrede, $briefanrede),
		]);
	}
}
