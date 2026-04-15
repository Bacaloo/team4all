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
	 * @param array<int, string>|string|null $addressBookIds
	 */
	#[AdminRequired]
	#[NoCSRFRequired]
	public function save(array|string|null $addressBookIds = null): RedirectResponse {
		$selected = [];
		if (is_array($addressBookIds)) {
			$selected = array_values($addressBookIds);
		} elseif (is_string($addressBookIds) && trim($addressBookIds) !== '') {
			$selected = [trim($addressBookIds)];
		}

		$this->addressBookSelectionService->saveSelectedAddressBookIds($selected);

		$target = $this->request->getHeader('Referer');
		if (!is_string($target) || trim($target) === '') {
			$target = $this->urlGenerator->getAbsoluteURL('/settings/admin/team4all');
		}

		return new RedirectResponse($target);
	}
}
