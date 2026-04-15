<?php

declare(strict_types=1);

namespace OCA\Team4All\Service;

use OCP\IUser;

class AddressBookAccessService {
	public function __construct(
		private GroupProvisioningService $groupProvisioningService,
	) {
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function getReadableAddressBooksForCurrentUser(object $cardDavBackend): array {
		$currentUser = $this->groupProvisioningService->getCurrentUser();
		if (!$currentUser instanceof IUser) {
			return [];
		}

		$principalUri = 'principals/users/' . $currentUser->getUID();
		$addressBooks = [];

		if (method_exists($cardDavBackend, 'getAddressBooksForUser')) {
			$addressBooks = $cardDavBackend->getAddressBooksForUser($principalUri);
		} elseif (method_exists($cardDavBackend, 'getUsersOwnAddressBooks')) {
			$addressBooks = $cardDavBackend->getUsersOwnAddressBooks($principalUri);
		}

		if (!is_array($addressBooks) || $addressBooks === []) {
			return [];
		}

		$filtered = [];
		foreach ($addressBooks as $addressBook) {
			if (!is_array($addressBook) || !isset($addressBook['id']) || !is_numeric($addressBook['id'])) {
				continue;
			}

			$filtered[(int)$addressBook['id']] = $addressBook;
		}

		return array_values($this->filterReadableAddressBooksForCurrentUser($filtered, $currentUser));
	}

	/**
	 * @param array<int, array<string, mixed>> $addressBooks
	 * @return array<int, array<string, mixed>>
	 */
	protected function filterReadableAddressBooksForCurrentUser(array $addressBooks, IUser $currentUser): array {
		return $addressBooks;
	}
}
