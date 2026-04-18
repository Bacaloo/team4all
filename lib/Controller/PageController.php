<?php

declare(strict_types=1);

namespace OCA\Team4All\Controller;

use OCA\Team4All\Service\AppAccessService;
use OCA\Team4All\Service\ContactGroupFilterService;
use OCA\Team4All\Service\ContactGroupProvisioningService;
use OCA\Team4All\Service\DocumentReferenceService;
use OCA\Team4All\Service\GroupProvisioningService;
use OCA\Team4All\Service\TeamFolderProvisioningService;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
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
		private AppAccessService $appAccessService,
		private ContactGroupFilterService $contactGroupFilterService,
		private ContactGroupProvisioningService $contactGroupProvisioningService,
		private DocumentReferenceService $documentReferenceService,
		private GroupProvisioningService $groupProvisioningService,
		private TeamFolderProvisioningService $teamFolderProvisioningService,
	) {
		parent::__construct($appName, $request);
	}

	#[NoCSRFRequired]
	#[NoAdminRequired]
	public function index(): TemplateResponse {
		if (!$this->appAccessService->canCurrentUserAccess()) {
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

		$team4AllGroups = $this->attachDocumentsToGroupEntries(
			$this->contactGroupProvisioningService->getTeam4AllContactGroups()
		);

		return new TemplateResponse($this->appName, 'main', [
			'team4AllGroups' => $team4AllGroups,
			'team4AllGroupCount' => count($team4AllGroups),
			'frontendFilterGroups' => $this->contactGroupFilterService->getSelectedFrontendFilterGroups(),
			'movableAddressBooks' => $this->contactGroupProvisioningService->getMovableAddressBookOptions(),
		]);
	}

	#[NoAdminRequired]
	public function updateNote(string $uri = '', string $note = '', string $uid = '', string $addressBookId = '0'): JSONResponse {
		if (!$this->appAccessService->canCurrentUserAccess()) {
			return new JSONResponse([
				'saved' => false,
				'message' => 'Access denied.',
			], Http::STATUS_FORBIDDEN);
		}

		$saved = $this->contactGroupProvisioningService->updateContactNoteByUri($uri, $note, $uid, (int)$addressBookId);

		return new JSONResponse([
			'saved' => $saved,
		], $saved ? Http::STATUS_OK : Http::STATUS_BAD_REQUEST);
	}

	#[NoAdminRequired]
	public function fetchContact(string $uid = '', string $uri = '', string $addressBookId = '0'): JSONResponse {
		if (!$this->appAccessService->canCurrentUserAccess()) {
			return new JSONResponse([
				'found' => false,
				'message' => 'Access denied.',
			], Http::STATUS_FORBIDDEN);
		}

		$resolvedAddressBookId = (int)$addressBookId;
		$contact = $uid !== ''
			? $this->contactGroupProvisioningService->getContactByUid($uid, $resolvedAddressBookId)
			: $this->contactGroupProvisioningService->getContactByUri($uri, $resolvedAddressBookId);
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

	#[NoAdminRequired]
	public function updateContact(
		string $uri = '',
		string $prefix = '',
		string $firstName = '',
		string $lastName = '',
		string $addressType = 'work',
		string $streetAddress = '',
		string $postalCode = '',
		string $locality = '',
		string $telephones = '',
		string $emails = '',
		string $uid = '',
		string $addressBookId = '0',
	): JSONResponse {
		if (!$this->appAccessService->canCurrentUserAccess()) {
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
			$uid,
			(int)$addressBookId,
		);

		return new JSONResponse([
			'saved' => $saved,
		], $saved ? Http::STATUS_OK : Http::STATUS_BAD_REQUEST);
	}

	#[NoAdminRequired]
	public function moveGroup(string $company = '', string $targetAddressBookId = '0'): JSONResponse {
		if (!$this->appAccessService->canCurrentUserAccess()) {
			return new JSONResponse([
				'moved' => false,
				'message' => 'Access denied.',
			], Http::STATUS_FORBIDDEN);
		}

		$moved = $this->contactGroupProvisioningService->moveGroupToAddressBook(
			$company,
			(int)$targetAddressBookId,
		);

		return new JSONResponse([
			'moved' => $moved,
		], $moved ? Http::STATUS_OK : Http::STATUS_BAD_REQUEST);
	}

	#[NoAdminRequired]
	public function moveContact(
		string $company = '',
		string $uid = '',
		string $uri = '',
		string $addressBookId = '0',
		string $targetAddressBookId = '0',
	): JSONResponse {
		if (!$this->appAccessService->canCurrentUserAccess()) {
			return new JSONResponse([
				'moved' => false,
				'message' => 'Access denied.',
			], Http::STATUS_FORBIDDEN);
		}

		$moved = $this->contactGroupProvisioningService->moveContactWithinGroup(
			$company,
			$uid,
			$uri,
			(int)$addressBookId,
			(int)$targetAddressBookId,
		);

		return new JSONResponse([
			'moved' => $moved,
		], $moved ? Http::STATUS_OK : Http::STATUS_BAD_REQUEST);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function downloadGroupVCard(string $company = ''): DataDownloadResponse|JSONResponse {
		if (!$this->appAccessService->canCurrentUserAccess()) {
			return new JSONResponse([
				'download' => false,
				'message' => 'Access denied.',
			], Http::STATUS_FORBIDDEN);
		}

		$vCardDownload = $this->contactGroupProvisioningService->buildGroupVCardDownload($company);
		if ($vCardDownload === null) {
			return new JSONResponse([
				'download' => false,
			], Http::STATUS_NOT_FOUND);
		}

		return new DataDownloadResponse(
			$vCardDownload['content'],
			$vCardDownload['filename'],
			'text/vcard; charset=utf-8',
		);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function downloadContactVCard(
		string $uid = '',
		string $uri = '',
		string $addressBookId = '0',
	): DataDownloadResponse|JSONResponse {
		if (!$this->appAccessService->canCurrentUserAccess()) {
			return new JSONResponse([
				'download' => false,
				'message' => 'Access denied.',
			], Http::STATUS_FORBIDDEN);
		}

		$vCardDownload = $this->contactGroupProvisioningService->buildContactVCardDownload(
			$uid,
			$uri,
			(int)$addressBookId,
		);
		if ($vCardDownload === null) {
			return new JSONResponse([
				'download' => false,
			], Http::STATUS_NOT_FOUND);
		}

		return new DataDownloadResponse(
			$vCardDownload['content'],
			$vCardDownload['filename'],
			'text/vcard; charset=utf-8',
		);
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

	/**
	 * @param list<array<string, mixed>> $groupEntries
	 * @return list<array<string, mixed>>
	 */
	private function attachDocumentsToGroupEntries(array $groupEntries): array {
		$documentsByContactUid = $this->documentReferenceService->getDocumentsByContactUids(
			$this->collectDocumentContactUids($groupEntries)
		);

		foreach ($groupEntries as &$entry) {
			if (($entry['type'] ?? '') === 'person' && is_array($entry['person'] ?? null)) {
				$personUid = trim((string)($entry['person']['uid'] ?? ''));
				$entry['person']['documents'] = $documentsByContactUid[$personUid] ?? [];
				continue;
			}

			$leaderDocuments = [];
			if (is_array($entry['leader'] ?? null)) {
				$leaderUid = trim((string)($entry['leader']['uid'] ?? ''));
				$leaderDocuments = $documentsByContactUid[$leaderUid] ?? [];
				$entry['leader']['documents'] = $leaderDocuments;
			}

			$groupDocuments = $leaderDocuments;
			$members = is_array($entry['members'] ?? null) ? $entry['members'] : [];
			foreach ($members as &$member) {
				if (!is_array($member)) {
					continue;
				}

				$memberUid = trim((string)($member['uid'] ?? ''));
				$memberDocuments = $documentsByContactUid[$memberUid] ?? [];
				$member['documents'] = $memberDocuments;
				$groupDocuments = $this->mergeDocuments($groupDocuments, $memberDocuments);
			}
			unset($member);

			$entry['members'] = $members;
			$entry['documents'] = $groupDocuments;
		}
		unset($entry);

		return $groupEntries;
	}

	/**
	 * @param list<array<string, mixed>> $groupEntries
	 * @return list<string>
	 */
	private function collectDocumentContactUids(array $groupEntries): array {
		$contactUids = [];

		foreach ($groupEntries as $entry) {
			if (($entry['type'] ?? '') === 'person' && is_array($entry['person'] ?? null)) {
				$uid = trim((string)($entry['person']['uid'] ?? ''));
				if ($uid !== '') {
					$contactUids[] = $uid;
				}
				continue;
			}

			if (is_array($entry['leader'] ?? null)) {
				$uid = trim((string)($entry['leader']['uid'] ?? ''));
				if ($uid !== '') {
					$contactUids[] = $uid;
				}
			}

			foreach (($entry['members'] ?? []) as $member) {
				if (!is_array($member)) {
					continue;
				}

				$uid = trim((string)($member['uid'] ?? ''));
				if ($uid !== '') {
					$contactUids[] = $uid;
				}
			}
		}

		return array_values(array_unique($contactUids));
	}

	/**
	 * @param list<array{contactUid:string,fileName:string,subject:string,documentCreatedAt:?string}> $documents
	 * @param list<array{contactUid:string,fileName:string,subject:string,documentCreatedAt:?string}> $additionalDocuments
	 * @return list<array{contactUid:string,fileName:string,subject:string,documentCreatedAt:?string}>
	 */
	private function mergeDocuments(array $documents, array $additionalDocuments): array {
		$seen = [];
		$merged = [];

		foreach (array_merge($documents, $additionalDocuments) as $document) {
			$key = ($document['contactUid'] ?? '') . '|' . ($document['fileName'] ?? '');
			if ($key === '|' || isset($seen[$key])) {
				continue;
			}

			$seen[$key] = true;
			$merged[] = $document;
		}

		usort($merged, static function (array $left, array $right): int {
			return strcmp((string)($right['documentCreatedAt'] ?? ''), (string)($left['documentCreatedAt'] ?? ''))
				?: strcmp((string)($left['subject'] ?? ''), (string)($right['subject'] ?? ''));
		});

		return $merged;
	}
}
