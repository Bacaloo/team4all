<?php

declare(strict_types=1);

namespace OCA\Team4All\Service;

use OCP\IServerContainer;
use Psr\Container\ContainerExceptionInterface;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Reader;
use Throwable;

class ContactGroupCatalogService {
	private const CARD_DAV_BACKEND_CLASS = 'OCA\\DAV\\CardDAV\\CardDavBackend';

	public function __construct(
		private GroupProvisioningService $groupProvisioningService,
		private AddressBookAccessService $addressBookAccessService,
		private IServerContainer $serverContainer,
	) {
	}

	/**
	 * @return list<string>
	 */
	public function getAvailableContactGroupsForTeam(): array {
		$cardDavBackend = $this->resolveCardDavBackend();
		if ($cardDavBackend === null) {
			return [];
		}

		$groupNames = [];
		$processedAddressBooks = [];

		foreach ($this->groupProvisioningService->getTeam4AllGroupUsers() as $user) {
			$principalUri = 'principals/users/' . $user->getUID();
			$addressBooks = $this->addressBookAccessService->getAddressBooksForPrincipal($cardDavBackend, $principalUri);

			foreach ($addressBooks as $addressBook) {
				$identity = $this->addressBookAccessService->getAddressBookIdentity($addressBook);
				if ($identity === '' || isset($processedAddressBooks[$identity]) || !isset($addressBook['id'])) {
					continue;
				}

				$processedAddressBooks[$identity] = true;
				$cards = $cardDavBackend->getCards((int)$addressBook['id']);

				foreach ($cards as $card) {
					if (!isset($card['carddata']) || !is_string($card['carddata'])) {
						continue;
					}

					$vCard = $this->parseVCard($card['carddata']);
					if (!$vCard instanceof VCard) {
						continue;
					}

					$allCategories = $this->extractCategories($vCard);
					if (!in_array(ContactGroupProvisioningService::CONTACT_GROUP_NAME, $allCategories, true)) {
						continue;
					}

					foreach ($this->extractVisibleContactGroups($vCard) as $groupName) {
						$groupNames[mb_strtolower($groupName)] = $groupName;
					}
				}
			}
		}

		natcasesort($groupNames);

		return array_values($groupNames);
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

	private function parseVCard(string $cardData): ?VCard {
		try {
			$vCard = Reader::read($cardData);
		} catch (Throwable) {
			return null;
		}

		return $vCard instanceof VCard ? $vCard : null;
	}

	/**
	 * @return list<string>
	 */
	private function extractCategories(VCard $vCard): array {
		$categories = [];

		foreach ($vCard->select('CATEGORIES') as $categoriesProperty) {
			$values = explode(',', (string)$categoriesProperty->getValue());
			foreach ($values as $value) {
				$value = trim($value);
				if ($value !== '') {
					$categories[$value] = $value;
				}
			}
		}

		return array_values($categories);
	}

	/**
	 * @return list<string>
	 */
	private function extractVisibleContactGroups(VCard $vCard): array {
		return array_values(array_filter(
			$this->extractCategories($vCard),
			static fn(string $category): bool => $category !== ContactGroupProvisioningService::CONTACT_GROUP_NAME
		));
	}
}
