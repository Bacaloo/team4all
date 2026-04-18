<?php

declare(strict_types=1);

namespace OCA\Team4All\Settings;

use OCA\Team4All\Service\AddressBookSelectionService;
use OCA\Team4All\Service\ContactGroupCatalogService;
use OCA\Team4All\Service\ContactGroupFilterService;
use OCA\Team4All\Service\DocumentReferenceSyncService;
use OCA\Team4All\Service\TeamAddressBookCatalogService;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;

class AdminSettings implements ISettings {
	public function __construct(
		private TeamAddressBookCatalogService $teamAddressBookCatalogService,
		private AddressBookSelectionService $addressBookSelectionService,
		private ContactGroupCatalogService $contactGroupCatalogService,
		private ContactGroupFilterService $contactGroupFilterService,
		private DocumentReferenceSyncService $documentReferenceSyncService,
		private IAppManager $appManager,
		private IRequest $request,
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getForm(): TemplateResponse {
		$addressBooksByUser = $this->teamAddressBookCatalogService->getSharedAddressBookOptionsForTeamByUser();
		$selectedAddressBookIdsByUser = [];
		$defaultAddressBookIdsByUser = [];
		$documentOverview = $this->documentReferenceSyncService->getOverview();
		$showUnassignedDocuments = $this->request->getParam('showUnassignedDocuments') === '1';

		foreach ($addressBooksByUser as $userEntry) {
			$userUid = trim((string)($userEntry['uid'] ?? ''));
			if ($userUid === '') {
				continue;
			}

			$selectedAddressBookIdsByUser[$userUid] = $this->addressBookSelectionService->getSelectedAddressBookIds($userUid);
			$defaultAddressBookIdsByUser[$userUid] = $this->addressBookSelectionService->getDefaultAddressBookId($userUid);
		}

		return new TemplateResponse('team4all', 'admin', [
			'addressBooksByUser' => $addressBooksByUser,
			'selectedAddressBookIdsByUser' => $selectedAddressBookIdsByUser,
			'defaultAddressBookIdsByUser' => $defaultAddressBookIdsByUser,
			'availableContactGroups' => $this->contactGroupCatalogService->getAvailableContactGroupsForTeam(),
			'selectedFrontendFilterGroups' => $this->contactGroupFilterService->getSelectedFrontendFilterGroups(),
			'documentReferenceFiles' => $showUnassignedDocuments ? $this->documentReferenceSyncService->getUnassignedFileNames() : [],
			'documentReferenceTotal' => $documentOverview['total'],
			'documentReferenceUnassigned' => $documentOverview['unassigned'],
			'showUnassignedDocuments' => $showUnassignedDocuments,
			'documentSyncUrl' => $this->urlGenerator->linkToRoute('team4all.adminSettings.syncDocumentReferences'),
			'documentSyncMessage' => $this->buildDocumentSyncMessage(),
			'showUnassignedDocumentsUrl' => $this->urlGenerator->getAbsoluteURL('/settings/admin/team4all?showUnassignedDocuments=1'),
			'pageTitle' => $this->l10n->t('Nutzbare Adressbücher'),
			'saveUrl' => $this->urlGenerator->linkToRoute('team4all.adminSettings.save'),
			'appVersion' => $this->appManager->getAppVersion('team4all'),
		]);
	}

	public function getSection(): string {
		return 'team4all';
	}

	public function getPriority(): int {
		return 10;
	}

	private function buildDocumentSyncMessage(): ?string {
		if ($this->request->getParam('t4aDocumentSync') !== '1') {
			return null;
		}

		$added = (int)$this->request->getParam('t4aDocumentSyncAdded', '0');
		$deleted = (int)$this->request->getParam('t4aDocumentSyncDeleted', '0');
		$total = (int)$this->request->getParam('t4aDocumentSyncTotal', '0');

		return sprintf(
			'Dokumentabgleich abgeschlossen: %d ergänzt, %d gelöscht, %d Einträge im Verzeichnis.',
			$added,
			$deleted,
			$total
		);
	}
}
