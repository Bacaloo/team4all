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
		$ownerUid = $this->extractOwnerUid($addressBook);
		$uri = $this->normalizeComparableUri(trim((string)($addressBook['uri'] ?? '')), $ownerUid);

		if ($ownerUid !== '' && $uri !== '') {
			return 'owner:' . $ownerUid . '|uri:' . $uri;
		}

		$principalUri = trim((string)($addressBook['principaluri'] ?? ''));

		return $principalUri !== '' && $uri !== '' ? $principalUri . '|' . $uri : '';
	}

	/**
	 * @param array<string, mixed> $addressBook
	 */
	public function extractOwnerUid(array $addressBook): string {
		$ownerPrincipal = trim((string)($addressBook['{http://owncloud.org/ns}owner-principal'] ?? ''));
		if (preg_match('#principals/users/([^/]+)$#', $ownerPrincipal, $matches) === 1) {
			return $matches[1];
		}

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

	private function normalizeComparableUri(string $uri, string $ownerUid): string {
		if ($uri === '' || $ownerUid === '') {
			return $uri;
		}

		$suffix = '_shared_by_' . $ownerUid;
		if (str_ends_with($uri, $suffix)) {
			return substr($uri, 0, -strlen($suffix)) ?: $uri;
		}

		return $uri;
	}

	/**
	 * @param list<array<string, mixed>> $addressBooks
	 * @return array<string, mixed>|null
	 */
	public function findAddressBookByIdentity(array $addressBooks, string $identity): ?array {
		$identity = trim($identity);
		if ($identity === '') {
			return null;
		}

		foreach ($addressBooks as $addressBook) {
			if ($this->getAddressBookIdentity($addressBook) === $identity) {
				return $addressBook;
			}
		}

		return null;
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
