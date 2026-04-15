<?php

declare(strict_types=1);

namespace OCA\Team4All\Service;

use OCP\IUser;

class AddressBookAccessService {
	public function __construct(
		private GroupProvisioningService $groupProvisioningService,
		private AddressBookSelectionService $addressBookSelectionService,
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
		$addressBooks = $this->getAddressBooksForPrincipal($cardDavBackend, $principalUri);
		if ($addressBooks === []) {
			return [];
		}

		return array_values($this->filterReadableAddressBooksForCurrentUser($addressBooks, $currentUser));
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function getAddressBooksForPrincipal(object $cardDavBackend, string $principalUri): array {
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

		return array_values($filtered);
	}

	/**
	 * @param array<string, mixed> $addressBook
	 */
	public function getAddressBookIdentity(array $addressBook): string {
		$principalUri = trim((string)($addressBook['principaluri'] ?? ''));
		$uri = trim((string)($addressBook['uri'] ?? ''));

		return $principalUri !== '' && $uri !== '' ? $principalUri . '|' . $uri : '';
	}

	/**
	 * @param array<string, mixed> $addressBook
	 */
	public function extractOwnerUid(array $addressBook): string {
		$principalUri = (string)($addressBook['principaluri'] ?? '');
		if (preg_match('#principals/users/([^/]+)$#', $principalUri, $matches) === 1) {
			return $matches[1];
		}

		return '';
	}

	/**
	 * @param array<string, mixed> $addressBook
	 */
	public function getDisplayName(array $addressBook): string {
		$displayName = trim((string)($addressBook['{DAV:}displayname'] ?? ''));

		return $displayName !== '' ? $displayName : trim((string)($addressBook['uri'] ?? ''));
	}

	/**
	 * @param array<int, array<string, mixed>> $addressBooks
	 * @return array<int, array<string, mixed>>
	 */
	protected function filterReadableAddressBooksForCurrentUser(array $addressBooks, IUser $currentUser): array {
		$selectedAddressBookIds = $this->addressBookSelectionService->getSelectedAddressBookIds();
		if ($selectedAddressBookIds === []) {
			return $addressBooks;
		}

		$addressBooks = array_filter($addressBooks, function (array $addressBook) use ($selectedAddressBookIds): bool {
			$identity = $this->getAddressBookIdentity($addressBook);

			return $identity !== '' && in_array($identity, $selectedAddressBookIds, true);
		});

		return $addressBooks;
	}
}
