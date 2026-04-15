<?php

declare(strict_types=1);

namespace OCA\Team4All\Settings;

use OCA\Team4All\Service\AddressBookSelectionService;
use OCA\Team4All\Service\ContactGroupCatalogService;
use OCA\Team4All\Service\ContactGroupFilterService;
use OCA\Team4All\Service\TeamAddressBookCatalogService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;

class AdminSettings implements ISettings {
	public function __construct(
		private TeamAddressBookCatalogService $teamAddressBookCatalogService,
		private AddressBookSelectionService $addressBookSelectionService,
		private ContactGroupCatalogService $contactGroupCatalogService,
		private ContactGroupFilterService $contactGroupFilterService,
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getForm(): TemplateResponse {
		$addressBooksByUser = $this->teamAddressBookCatalogService->getSharedAddressBookOptionsForTeamByUser();
		$selectedAddressBookIdsByUser = [];
		$defaultAddressBookIdsByUser = [];

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
			'pageTitle' => $this->l10n->t('Nutzbare Adressbücher'),
			'saveUrl' => $this->urlGenerator->linkToRoute('team4all.adminSettings.save'),
		]);
	}

	public function getSection(): string {
		return 'team4all';
	}

	public function getPriority(): int {
		return 10;
	}
}
