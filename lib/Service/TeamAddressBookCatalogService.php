<?php

declare(strict_types=1);

namespace OCA\Team4All\Service;

use OCP\IUser;
use OCP\IServerContainer;
use Psr\Container\ContainerExceptionInterface;

class TeamAddressBookCatalogService {
	private const CARD_DAV_BACKEND_CLASS = 'OCA\\DAV\\CardDAV\\CardDavBackend';

	public function __construct(
		private GroupProvisioningService $groupProvisioningService,
		private AddressBookAccessService $addressBookAccessService,
		private IServerContainer $serverContainer,
	) {
	}

	/**
	 * @return list<array{id:string,label:string,ownerUid:string,uri:string,displayName:string,visibleFor:list<string>}>
	 */
	public function getSharedAddressBookOptionsForTeam(): array {
		$cardDavBackend = $this->resolveCardDavBackend();
		if ($cardDavBackend === null) {
			return [];
		}

		$booksById = [];

		foreach ($this->groupProvisioningService->getTeam4AllGroupUsers() as $user) {
			$viewerUid = $user->getUID();
			$principalUri = 'principals/users/' . $viewerUid;
			$addressBooks = $this->addressBookAccessService->getAddressBooksForPrincipal($cardDavBackend, $principalUri);

			foreach ($addressBooks as $addressBook) {
				$identity = $this->addressBookAccessService->getAddressBookIdentity($addressBook);
				if ($identity === '') {
					continue;
				}

				$ownerUid = $this->addressBookAccessService->extractOwnerUid($addressBook);
				$booksById[$identity] ??= [
					'id' => $identity,
					'label' => $this->buildLabel($addressBook),
					'ownerUid' => $ownerUid,
					'uri' => (string)($addressBook['uri'] ?? ''),
					'displayName' => $this->addressBookAccessService->getDisplayName($addressBook),
					'visibleFor' => [],
				];

				if (!in_array($viewerUid, $booksById[$identity]['visibleFor'], true)) {
					$booksById[$identity]['visibleFor'][] = $viewerUid;
				}
			}
		}

		$sharedBooks = array_values(array_filter(
			$booksById,
			static fn(array $book): bool => count($book['visibleFor']) > 1
		));

		usort($sharedBooks, static function (array $left, array $right): int {
			return strcasecmp($left['label'], $right['label']);
		});

		return $sharedBooks;
	}

	private function resolveCardDavBackend(): ?object {
		if (!class_exists(self::CARD_DAV_BACKEND_CLASS)) {
			return null;
		}

		try {
			$backend = $this->serverContainer->get(self::CARD_DAV_BACKEND_CLASS);
		} catch (ContainerExceptionInterface) {
			return null;
		}

		return is_object($backend) ? $backend : null;
	}

	/**
	 * @param array<string, mixed> $addressBook
	 */
	private function buildLabel(array $addressBook): string {
		$displayName = $this->addressBookAccessService->getDisplayName($addressBook);
		$ownerUid = $this->addressBookAccessService->extractOwnerUid($addressBook);

		if ($ownerUid === '') {
			return $displayName;
		}

		return $displayName . ' (' . $ownerUid . ')';
	}
}
