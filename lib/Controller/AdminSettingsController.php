<?php

declare(strict_types=1);

namespace OCA\Team4All\Controller;

use OCA\Team4All\AppInfo\Application;
use OCA\Team4All\Service\AddressBookSelectionService;
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
		private IURLGenerator $urlGenerator,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * @param array<string, array<int, string>|string>|null $addressBookIds
	 * @param array<string, string>|null $defaultAddressBookId
	 * @param array<int, string>|null $teamUserUids
	 */
	#[AdminRequired]
	#[NoCSRFRequired]
	public function save(array|null $addressBookIds = null, array|null $defaultAddressBookId = null, array|null $teamUserUids = null): RedirectResponse {
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

		$target = $this->request->getHeader('Referer');
		if (!is_string($target) || trim($target) === '') {
			$target = $this->urlGenerator->getAbsoluteURL('/settings/admin/team4all');
		}

		return new RedirectResponse($target);
	}
}
