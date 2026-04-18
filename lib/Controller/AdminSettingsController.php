<?php

declare(strict_types=1);

namespace OCA\Team4All\Controller;

use OCA\Team4All\AppInfo\Application;
use OCA\Team4All\Service\AddressBookSelectionService;
use OCA\Team4All\Service\ContactGroupFilterService;
use OCA\Team4All\Service\DocumentReferenceSyncService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IRequest;
use OCP\IURLGenerator;

class AdminSettingsController extends Controller {
	public function __construct(
		IRequest $request,
		private AddressBookSelectionService $addressBookSelectionService,
		private ContactGroupFilterService $contactGroupFilterService,
		private DocumentReferenceSyncService $documentReferenceSyncService,
		private IURLGenerator $urlGenerator,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * @param array<string, array<int, string>|string>|null $addressBookIds
	 * @param array<string, string>|null $defaultAddressBookId
	 * @param array<int, string>|null $teamUserUids
	 * @param array<int, string>|string|null $frontendFilterGroups
	 */
	#[AdminRequired]
	#[NoCSRFRequired]
	public function save(
		array|null $addressBookIds = null,
		array|null $defaultAddressBookId = null,
		array|null $teamUserUids = null,
		array|string|null $frontendFilterGroups = null,
		string $adminAction = '',
	): RedirectResponse {
		if ($adminAction === 'syncDocumentReferences') {
			return $this->buildSyncRedirectResponse();
		}

		foreach ($teamUserUids ?? [] as $userUid) {
			if (!is_string($userUid) || trim($userUid) === '') {
				continue;
			}

			$selected = [];
			$selectedRaw = $addressBookIds[$userUid] ?? null;
			if (is_array($selectedRaw)) {
				$selected = array_values(array_filter($selectedRaw, static fn(mixed $value): bool => is_string($value)));
			} elseif (is_string($selectedRaw) && trim($selectedRaw) !== '') {
				$selected = [trim($selectedRaw)];
			}

			$this->addressBookSelectionService->saveSelectedAddressBookIdsForUser($userUid, $selected);
			$this->addressBookSelectionService->saveDefaultAddressBookIdForUser(
				$userUid,
				is_string($defaultAddressBookId[$userUid] ?? null) ? (string)$defaultAddressBookId[$userUid] : '',
			);
		}

		$selectedFrontendFilterGroups = [];
		if (is_array($frontendFilterGroups)) {
			$selectedFrontendFilterGroups = array_values(array_filter($frontendFilterGroups, static fn(mixed $value): bool => is_string($value)));
		} elseif (is_string($frontendFilterGroups) && trim($frontendFilterGroups) !== '') {
			$selectedFrontendFilterGroups = [trim($frontendFilterGroups)];
		}

		$this->contactGroupFilterService->saveSelectedFrontendFilterGroups($selectedFrontendFilterGroups);

		$target = $this->request->getHeader('Referer');
		if (!is_string($target) || trim($target) === '') {
			$target = $this->urlGenerator->getAbsoluteURL('/settings/admin/team4all');
		}

		return new RedirectResponse($target);
	}

	#[AdminRequired]
	#[NoCSRFRequired]
	public function syncDocumentReferences(): RedirectResponse {
		return $this->buildSyncRedirectResponse();
	}

	private function buildSyncRedirectResponse(): RedirectResponse {
		$result = $this->documentReferenceSyncService->syncFromDocumentsFolder();

		$target = $this->request->getHeader('Referer');
		if (!is_string($target) || trim($target) === '') {
			$target = $this->urlGenerator->getAbsoluteURL('/settings/admin/team4all');
		}

		$separator = str_contains($target, '?') ? '&' : '?';
		$target .= $separator . http_build_query([
			't4aDocumentSync' => '1',
			't4aDocumentSyncAdded' => (string)$result['added'],
			't4aDocumentSyncDeleted' => (string)$result['deleted'],
			't4aDocumentSyncTotal' => (string)$result['total'],
		]);

		return new RedirectResponse($target);
	}
}
