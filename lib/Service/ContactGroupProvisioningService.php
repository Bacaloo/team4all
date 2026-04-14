<?php

declare(strict_types=1);

namespace OCA\Team4All\Service;

use OCP\IServerContainer;
use OCP\IUser;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Reader;
use Throwable;

class ContactGroupProvisioningService {
	public const CONTACT_GROUP_NAME = 'Team4All';
	private const CARD_DAV_BACKEND_CLASS = 'OCA\\DAV\\CardDAV\\CardDavBackend';
	private const MANAGED_CONTACT_UID_PREFIX = 'team4all-provisioning-';
	private const MANAGED_CONTACT_URI_PREFIX = 'team4all-provisioning-';
	private const GROUP_LEADER_UID_PREFIX = 'team4all-group-leader-';
	private const GROUP_LEADER_URI_PREFIX = 'team4all-group-leader-';
	private const DEFAULT_ADDRESSBOOK_URI = 'contacts';
	private const TEAM4ALL_ADDRESSBOOK_URI = 'team4all';
	private const TEAM4ALL_ADDRESSBOOK_DISPLAY_NAME = 'Team4All';

	public function __construct(
		private GroupProvisioningService $groupProvisioningService,
		private IServerContainer $serverContainer,
		private LoggerInterface $logger,
	) {
	}

	public function ensureTeam4AllContactGroup(): void {
		try {
			$provisioningUser = $this->groupProvisioningService->getProvisioningUser();
			if (!$provisioningUser instanceof IUser) {
				$this->logger->warning('Skipped Team4All contact group provisioning because no provisioning user could be resolved.');
				return;
			}

			$this->logger->info('Starting Team4All contact group provisioning.', [
				'uid' => $provisioningUser->getUID(),
			]);

			$cardDavBackend = $this->resolveCardDavBackend();
			if ($cardDavBackend === null) {
				$this->logger->warning('Skipped Team4All contact group provisioning because the CardDAV backend could not be resolved.');
				return;
			}

			$principalUri = 'principals/users/' . $provisioningUser->getUID();
			$addressBook = $this->resolveContactsAddressBook($cardDavBackend, $principalUri);
			if ($addressBook === null || !isset($addressBook['id'])) {
				$this->logger->warning('Skipped Team4All contact group provisioning because the default contacts address book could not be resolved.', [
					'uid' => $provisioningUser->getUID(),
				]);
				return;
			}

			$this->logger->info('Resolved default contacts address book for Team4All contact group.', [
				'uid' => $provisioningUser->getUID(),
				'addressBookId' => (int)$addressBook['id'],
				'addressBookUri' => (string)($addressBook['uri'] ?? ''),
			]);

			$addressBookId = (int)$addressBook['id'];
			$existingCards = $cardDavBackend->getCards($addressBookId);
			$managedUid = $this->buildManagedContactUid($provisioningUser);
			$managedUri = $this->buildManagedContactUri($provisioningUser);
			$matchingCard = $this->findMatchingCard($existingCards, $provisioningUser, $managedUid);

			$this->logger->info('Evaluated provisioning contact candidate for Team4All contact group.', [
				'uid' => $provisioningUser->getUID(),
				'addressBookId' => $addressBookId,
				'matchedExistingCard' => $matchingCard !== null,
				'matchedCardUri' => $matchingCard['uri'] ?? null,
				'managedCardUri' => $managedUri,
			]);

			if ($matchingCard !== null && $this->cardContainsTeam4AllCategory((string)$matchingCard['carddata'])) {
				$this->logger->info('Verified Team4All provisioning contact in default contacts address book.', [
					'uid' => $provisioningUser->getUID(),
					'addressBookId' => $addressBookId,
					'cardUri' => (string)$matchingCard['uri'],
				]);
				return;
			}

			if ($matchingCard !== null) {
				$vCard = $this->parseVCard($matchingCard['carddata']);
				if ($vCard instanceof VCard) {
					$this->applyManagedContactData($vCard, $provisioningUser, $managedUid);
					$cardDavBackend->updateCard($addressBookId, $matchingCard['uri'], $vCard->serialize());
					$this->logger->info('Updated provisioning contact in default contacts address book with Team4All contact group membership.', [
						'uid' => $provisioningUser->getUID(),
						'addressBookId' => $addressBookId,
						'cardUri' => $matchingCard['uri'],
					]);
					return;
				}
			}

			$vCard = new VCard();
			$this->applyManagedContactData($vCard, $provisioningUser, $managedUid);
			$cardDavBackend->createCard($addressBookId, $managedUri, $vCard->serialize());

			$this->logger->info('Created provisioning contact in default contacts address book with Team4All contact group membership.', [
				'uid' => $provisioningUser->getUID(),
				'addressBookId' => $addressBookId,
				'cardUri' => $managedUri,
			]);
		} catch (Throwable $exception) {
			$this->logger->error('Failed to ensure Team4All contact group.', [
				'exception' => $exception,
			]);
		}
	}

	public function getTeam4AllContactGroups(): array {
		$provisioningUser = $this->groupProvisioningService->getProvisioningUser();
		if (!$provisioningUser instanceof IUser) {
			return [];
		}

		$cardDavBackend = $this->resolveCardDavBackend();
		if ($cardDavBackend === null) {
			return [];
		}

		$principalUri = 'principals/users/' . $provisioningUser->getUID();
		$addressBook = $this->resolveContactsAddressBook($cardDavBackend, $principalUri);
		if ($addressBook === null || !isset($addressBook['id'])) {
			return [];
		}

		$cards = $cardDavBackend->getCards((int)$addressBook['id']);
		$contacts = $this->collectTeam4AllContactsFromCards($cards);

		$createdLeader = $this->ensureMissingGroupLeaderContacts(
			$cardDavBackend,
			(int)$addressBook['id'],
			$contacts,
		);
		if ($createdLeader) {
			$cards = $cardDavBackend->getCards((int)$addressBook['id']);
			$contacts = $this->collectTeam4AllContactsFromCards($cards);
		}

		return $this->groupContactsByCompany($contacts);
	}

	public function getContactByUri(string $uri): ?array {
		$uri = trim($uri);
		if ($uri === '') {
			return null;
		}

		$provisioningUser = $this->groupProvisioningService->getProvisioningUser();
		if (!$provisioningUser instanceof IUser) {
			return null;
		}

		$cardDavBackend = $this->resolveCardDavBackend();
		if ($cardDavBackend === null) {
			return null;
		}

		$principalUri = 'principals/users/' . $provisioningUser->getUID();
		$addressBook = $this->resolveContactsAddressBook($cardDavBackend, $principalUri);
		if ($addressBook === null || !isset($addressBook['id'])) {
			return null;
		}

		foreach ($cardDavBackend->getCards((int)$addressBook['id']) as $card) {
			if (($card['uri'] ?? '') !== $uri || !isset($card['carddata']) || !is_string($card['carddata'])) {
				continue;
			}

			$vCard = $this->parseVCard($card['carddata']);
			if (!$vCard instanceof VCard) {
				return null;
			}

			return $this->buildContactDataFromCard($vCard, (string)$card['uri']);
		}

		return null;
	}

	public function getContactByUid(string $uid): ?array {
		$uid = trim($uid);
		if ($uid === '') {
			return null;
		}

		$provisioningUser = $this->groupProvisioningService->getProvisioningUser();
		if (!$provisioningUser instanceof IUser) {
			return null;
		}

		$cardDavBackend = $this->resolveCardDavBackend();
		if ($cardDavBackend === null) {
			return null;
		}

		$principalUri = 'principals/users/' . $provisioningUser->getUID();
		$addressBook = $this->resolveContactsAddressBook($cardDavBackend, $principalUri);
		if ($addressBook === null || !isset($addressBook['id'])) {
			return null;
		}

		foreach ($cardDavBackend->getCards((int)$addressBook['id']) as $card) {
			if (!isset($card['carddata']) || !is_string($card['carddata'])) {
				continue;
			}

			$vCard = $this->parseVCard($card['carddata']);
			if (!$vCard instanceof VCard) {
				continue;
			}

			$currentUid = isset($vCard->UID) ? trim((string)$vCard->UID->getValue()) : '';
			if ($currentUid !== $uid) {
				continue;
			}

			return $this->buildContactDataFromCard($vCard, (string)($card['uri'] ?? ''));
		}

		return null;
	}

	public function updateContactNoteByUri(string $uri, string $note): bool {
		$uri = trim($uri);
		if ($uri === '') {
			return false;
		}

		$provisioningUser = $this->groupProvisioningService->getProvisioningUser();
		if (!$provisioningUser instanceof IUser) {
			return false;
		}

		$cardDavBackend = $this->resolveCardDavBackend();
		if ($cardDavBackend === null) {
			return false;
		}

		$principalUri = 'principals/users/' . $provisioningUser->getUID();
		$addressBook = $this->resolveContactsAddressBook($cardDavBackend, $principalUri);
		if ($addressBook === null || !isset($addressBook['id'])) {
			return false;
		}

		foreach ($cardDavBackend->getCards((int)$addressBook['id']) as $card) {
			if (($card['uri'] ?? '') !== $uri || !isset($card['carddata']) || !is_string($card['carddata'])) {
				continue;
			}

			$vCard = $this->parseVCard($card['carddata']);
			if (!$vCard instanceof VCard) {
				return false;
			}

			while (isset($vCard->NOTE)) {
				unset($vCard->NOTE);
			}

			if (trim($note) !== '') {
				$vCard->add('NOTE', $note);
			}

			$cardDavBackend->updateCard((int)$addressBook['id'], $uri, $vCard->serialize());

			return true;
		}

		return false;
	}

	public function updateContactDetailsByUri(
		string $uri,
		string $prefix,
		string $firstName,
		string $lastName,
		string $addressType,
		string $streetAddress,
		string $postalCode,
		string $locality,
		string $telephones,
		string $emails,
	): bool {
		$uri = trim($uri);
		if ($uri === '') {
			return false;
		}

		$provisioningUser = $this->groupProvisioningService->getProvisioningUser();
		if (!$provisioningUser instanceof IUser) {
			return false;
		}

		$cardDavBackend = $this->resolveCardDavBackend();
		if ($cardDavBackend === null) {
			return false;
		}

		$principalUri = 'principals/users/' . $provisioningUser->getUID();
		$addressBook = $this->resolveContactsAddressBook($cardDavBackend, $principalUri);
		if ($addressBook === null || !isset($addressBook['id'])) {
			return false;
		}

		foreach ($cardDavBackend->getCards((int)$addressBook['id']) as $card) {
			if (($card['uri'] ?? '') !== $uri || !isset($card['carddata']) || !is_string($card['carddata'])) {
				continue;
			}

			$vCard = $this->parseVCard($card['carddata']);
			if (!$vCard instanceof VCard) {
				return false;
			}

			$this->applyEditableContactDetails(
				$vCard,
				trim($prefix),
				trim($firstName),
				trim($lastName),
				trim($addressType),
				trim($streetAddress),
				trim($postalCode),
				trim($locality),
				$telephones,
				$emails,
			);

			$cardDavBackend->updateCard((int)$addressBook['id'], $uri, $vCard->serialize());

			return true;
		}

		return false;
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
	 * @return array<string, mixed>|null
	 */
	private function resolveContactsAddressBook(object $cardDavBackend, string $principalUri): ?array {
		$addressBooks = $cardDavBackend->getUsersOwnAddressBooks($principalUri);
		if (!empty($addressBooks)) {
			foreach ($addressBooks as $addressBook) {
				if (($addressBook['uri'] ?? null) === self::DEFAULT_ADDRESSBOOK_URI) {
					return $addressBook;
				}
			}
		}

		$addressBookId = $cardDavBackend->createAddressBook(
			$principalUri,
			self::DEFAULT_ADDRESSBOOK_URI,
			[
				'{DAV:}displayname' => 'Kontakte',
			],
		);

		$this->logger->info('Created default contacts address book for Team4All contact group provisioning.', [
			'principalUri' => $principalUri,
			'addressBookId' => (int)$addressBookId,
		]);

		return $cardDavBackend->getAddressBookById((int)$addressBookId);
	}

	/**
	 * @param array<int, array<string, mixed>> $cards
	 * @return array<string, mixed>|null
	 */
	private function findMatchingCard(array $cards, IUser $user, string $managedUid): ?array {
		$emailAddress = strtolower((string)$user->getEMailAddress());

		foreach ($cards as $card) {
			if (!isset($card['carddata']) || !is_string($card['carddata'])) {
				continue;
			}

			$vCard = $this->parseVCard($card['carddata']);
			if (!$vCard instanceof VCard) {
				continue;
			}

			$currentUid = isset($vCard->UID) ? trim((string)$vCard->UID->getValue()) : '';
			if ($currentUid === $managedUid) {
				return $card;
			}

			if ($emailAddress === '') {
				continue;
			}

			foreach ($vCard->select('EMAIL') as $emailProperty) {
				$currentEmail = strtolower(trim((string)$emailProperty->getValue()));
				if ($currentEmail !== '' && $currentEmail === $emailAddress) {
					return $card;
				}
			}
		}

		return null;
	}

	private function applyManagedContactData(VCard $vCard, IUser $user, string $managedUid): void {
		$displayName = $user->getDisplayName();
		$emailAddress = trim((string)$user->getEMailAddress());

		$vCard->UID = $managedUid;
		$vCard->FN = $displayName;

		if (!isset($vCard->N)) {
			$vCard->add('N', ['', $displayName, '', '', '']);
		}

		$emailExists = false;
		foreach ($vCard->select('EMAIL') as $emailProperty) {
			$currentEmail = strtolower(trim((string)$emailProperty->getValue()));
			if ($emailAddress !== '' && $currentEmail === strtolower($emailAddress)) {
				$emailExists = true;
				break;
			}
		}
		if ($emailAddress !== '' && !$emailExists) {
			$vCard->add('EMAIL', $emailAddress, ['TYPE' => 'INTERNET']);
		}

		$categories = $this->extractCategories($vCard);
		if (!in_array(self::CONTACT_GROUP_NAME, $categories, true)) {
			$vCard->add('CATEGORIES', self::CONTACT_GROUP_NAME);
		}
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
					$categories[] = $value;
				}
			}
		}

		return array_values(array_unique($categories));
	}

	/**
	 * @return list<string>
	 */
	private function extractVisibleContactGroups(VCard $vCard): array {
		return array_values(array_filter(
			$this->extractCategories($vCard),
			static fn(string $category): bool => $category !== self::CONTACT_GROUP_NAME
		));
	}

	private function extractCompany(VCard $vCard): string {
		$companies = $this->extractCompanies($vCard);

		return $companies[0] ?? '';
	}

	private function extractDisplayCompany(VCard $vCard): string {
		if (!isset($vCard->ORG)) {
			return '';
		}

		$value = $vCard->ORG->getValue();
		if (is_array($value)) {
			$value = $value[0] ?? '';
		}

		return $this->sanitizeVCardDisplayValue((string)$value);
	}

	/**
	 * @return list<string>
	 */
	private function extractCompanies(VCard $vCard): array {
		if (!isset($vCard->ORG)) {
			return [];
		}

		$value = $vCard->ORG->getValue();
		$rawCompanies = is_array($value) ? $value : [$value];
		$companies = [];

		foreach ($rawCompanies as $rawCompany) {
			foreach ($this->splitCompanyValue((string)$rawCompany) as $companyPart) {
				$company = $this->canonicalizeCompanyName($companyPart);
				if ($company !== '') {
					$companies[] = $company;
				}
			}
		}

		return array_values(array_unique($companies));
	}

	/**
	 * Akzeptiert aus Kompatibilitätsgründen aktuell "," und "|".
	 * Zukünftig ist "|" das führende Trennzeichen für mehrere Firmen.
	 *
	 * @return list<string>
	 */
	private function splitCompanyValue(string $value): array {
		$parts = preg_split('/[,\|]/u', $value) ?: [];

		return array_values(array_filter(array_map(
			static fn(string $part): string => trim(str_replace(';', ' ', $part), " \t\n\r\0\x0B;"),
			$parts
		), static fn(string $part): bool => $part !== ''));
	}

	private function canonicalizeCompanyName(string $value): string {
		$value = str_replace(';', ' ', $value);
		$value = trim($value, " \t\n\r\0\x0B;");
		$value = preg_replace('/\s+/', ' ', $value) ?? $value;
		if ($value === '') {
			return '';
		}

		if (preg_match('/^([[:alnum:]]{4})-([0-9]+)$/u', $value, $matches) === 1) {
			return $matches[1];
		}

		return $value;
	}

	/**
	 * @return array{lastName: string, firstName: string, additional: string, prefix: string, suffix: string}
	 */
	private function extractStructuredNameParts(VCard $vCard): array {
		if (!isset($vCard->N)) {
			return [
				'lastName' => '',
				'firstName' => '',
				'additional' => '',
				'prefix' => '',
				'suffix' => '',
			];
		}

		$value = $vCard->N->getValue();
		if (!is_array($value)) {
			$parts = array_pad(explode(';', (string)$value, 5), 5, '');

			return [
				'lastName' => $this->sanitizeVCardDisplayValue((string)($parts[0] ?? '')),
				'firstName' => $this->sanitizeVCardDisplayValue((string)($parts[1] ?? '')),
				'additional' => $this->sanitizeVCardDisplayValue((string)($parts[2] ?? '')),
				'prefix' => $this->sanitizeVCardDisplayValue((string)($parts[3] ?? '')),
				'suffix' => $this->sanitizeVCardDisplayValue((string)($parts[4] ?? '')),
			];
		}

		return [
			'lastName' => $this->sanitizeVCardDisplayValue((string)($value[0] ?? '')),
			'firstName' => $this->sanitizeVCardDisplayValue((string)($value[1] ?? '')),
			'additional' => $this->sanitizeVCardDisplayValue((string)($value[2] ?? '')),
			'prefix' => $this->sanitizeVCardDisplayValue((string)($value[3] ?? '')),
			'suffix' => $this->sanitizeVCardDisplayValue((string)($value[4] ?? '')),
		];
	}

	private function sanitizeVCardDisplayValue(string $value): string {
		$value = str_replace(
			['\\,', '\\;', '\\\\'],
			[',', ' ', '\\'],
			$value
		);
		$value = str_replace(';', ' ', $value);
		$value = trim($value, " \t\n\r\0\x0B;");
		$value = preg_replace('/\s+/', ' ', $value) ?? $value;

		return $value;
	}

	private function buildContactSearchText(string $formattedName, string $structuredName, string $company, string $email, string $telephone): string {
		return trim(implode(' ', array_filter([
			$formattedName,
			$structuredName,
			$company,
			$email,
			$telephone,
		], static fn(string $value): bool => trim($value) !== '')));
	}

	private function extractNote(VCard $vCard): string {
		foreach ($vCard->select('NOTE') as $noteProperty) {
			$note = trim((string)$noteProperty->getValue());
			if ($note !== '') {
				return $note;
			}
		}

		return '';
	}

	/**
	 * @return list<string>
	 */
	private function extractEmailValues(VCard $vCard): array {
		$emails = [];

		foreach ($vCard->select('EMAIL') as $emailProperty) {
			$email = trim((string)$emailProperty->getValue());
			if ($email !== '') {
				$emails[] = $email;
			}
		}

		return array_values(array_unique($emails));
	}

	/**
	 * @return list<array{label: string, value: string}>
	 */
	private function extractTelephoneEntries(VCard $vCard): array {
		$telephoneEntries = [];

		foreach ($vCard->select('TEL') as $telephoneProperty) {
			$telephone = trim((string)$telephoneProperty->getValue());
			if ($telephone === '') {
				continue;
			}

			$label = $this->buildTelephoneLabel($telephoneProperty);
			$key = $label . '|' . $telephone;
			$telephoneEntries[$key] = [
				'label' => $label,
				'value' => $telephone,
			];
		}

		return array_values($telephoneEntries);
	}

	private function buildTelephoneLabel($telephoneProperty): string {
		if (!isset($telephoneProperty['TYPE'])) {
			return 'Telefon';
		}

		$typeValue = (string)$telephoneProperty['TYPE']->getValue();
		$typeParts = array_map(
			static fn(string $part): string => mb_strtolower(trim($part)),
			explode(',', $typeValue)
		);

		if (in_array('cell', $typeParts, true) || in_array('mobile', $typeParts, true)) {
			return 'Mobil';
		}

		if (in_array('home', $typeParts, true)) {
			return 'Privat';
		}

		if (in_array('work', $typeParts, true)) {
			return 'Arbeit';
		}

		return 'Telefon';
	}

	private function composeStructuredName(array $nameParts): string {
		return trim(implode(' ', array_filter([
			$nameParts['prefix'] ?? '',
			$nameParts['firstName'] ?? '',
			$nameParts['additional'] ?? '',
			$nameParts['lastName'] ?? '',
			$nameParts['suffix'] ?? '',
		], static fn(string $value): bool => trim($value) !== '')));
	}

	/**
	 * @param array{lastName: string, firstName: string, additional: string, prefix: string, suffix: string} $nameParts
	 * @return array{prefix: string, firstName: string, lastName: string}
	 */
	private function buildEditableDisplayNameParts(string $formattedName, array $nameParts, string $company): array {
		$prefix = $nameParts['prefix'];
		$firstName = $nameParts['firstName'];
		$lastName = $nameParts['lastName'];

		if ($prefix === '' && $firstName === '' && $lastName === '') {
			$fallback = trim($formattedName);
			if ($fallback === '') {
				$fallback = trim($company);
			}
			if ($fallback !== '') {
				$lastName = $fallback;
			}
		}

		return [
			'prefix' => $prefix,
			'firstName' => $firstName,
			'lastName' => $lastName,
		];
	}

	private function extractStreetAddress(VCard $vCard): string {
		$value = $this->getPreferredAddressValue($vCard);
		return trim((string)($value[2] ?? ''));
	}

	private function extractPostalCode(VCard $vCard): string {
		$value = $this->getPreferredAddressValue($vCard);
		return trim((string)($value[5] ?? ''));
	}

	private function extractLocality(VCard $vCard): string {
		$value = $this->getPreferredAddressValue($vCard);
		return trim((string)($value[3] ?? ''));
	}

	private function extractAddressType(VCard $vCard): string {
		$address = $this->getPreferredAddress($vCard);

		return $address['type'];
	}

	private function groupContactsByCompany(array $contacts): array {
		$grouped = [];
		$entries = [];

		foreach ($contacts as $contact) {
			$companies = array_values(array_filter(
				$contact['companies'] ?? [$contact['company']],
				static fn(string $company): bool => trim($company) !== ''
			));

			if ($companies === []) {
				$entries[] = [
					'type' => 'person',
					'label' => $contact['name'],
					'company' => '',
					'leader' => null,
					'members' => [],
					'person' => $contact,
				];
				continue;
			}

			foreach ($companies as $company) {
				if (!isset($grouped[$company])) {
					$grouped[$company] = [
						'company' => $company,
						'leader' => null,
						'leaderCandidates' => [],
						'members' => [],
					];
				}

				if ($this->isLeaderContact($contact['rawName'], $contact['name'], $company)) {
					$grouped[$company]['leaderCandidates'][] = $contact;
					continue;
				}

				$grouped[$company]['members'][] = $contact;
			}
		}

		foreach ($grouped as &$group) {
			$group['leader'] = $this->pickPreferredLeaderCandidate($group['leaderCandidates']);
			unset($group['leaderCandidates']);

			if ($group['leader'] !== null) {
				$leaderUri = $group['leader']['uri'];
				$group['members'] = array_values(
					array_filter(
						$group['members'],
						static fn(array $member): bool => $member['uri'] !== $leaderUri
					)
				);
			}

			usort(
				$group['members'],
				static fn(array $left, array $right): int => strcasecmp($left['name'], $right['name'])
			);
		}
		unset($group);

		foreach (array_values($grouped) as $group) {
			$entries[] = [
				'type' => 'group',
				'label' => $group['company'],
				'company' => $group['company'],
				'leader' => $group['leader'],
				'members' => $group['members'],
				'person' => null,
			];
		}

		usort(
			$entries,
			static fn(array $left, array $right): int => strcasecmp($left['label'], $right['label'])
		);

		return $entries;
	}

	private function pickPreferredLeaderCandidate(array $candidates): ?array {
		if ($candidates === []) {
			return null;
		}

		usort($candidates, function (array $left, array $right): int {
			$leftScore = $this->leaderCandidateScore($left);
			$rightScore = $this->leaderCandidateScore($right);

			return $rightScore <=> $leftScore;
		});

		return $candidates[0];
	}

	private function isGeneratedGroupLeaderUri(string $uri): bool {
		return str_starts_with($uri, self::GROUP_LEADER_URI_PREFIX);
	}

	private function leaderCandidateScore(array $candidate): int {
		$score = 0;

		if (!$this->isGeneratedGroupLeaderUri($candidate['uri'])) {
			$score += 100;
		}

		if (trim($candidate['note']) !== '') {
			$score += 50;
		}

		if (trim($candidate['rawName']) !== '') {
			$score += 10;
		}

		return $score;
	}

	private function ensureMissingGroupLeaderContacts(object $cardDavBackend, int $addressBookId, array $contacts): bool {
		$entries = $this->groupContactsByCompany($contacts);
		$allCards = $cardDavBackend->getCards($addressBookId);
		$createdLeader = false;
		$knownUris = array_map(
			static fn(array $contact): string => (string)($contact['uri'] ?? ''),
			$contacts
		);

		foreach ($entries as $entry) {
			if ($entry['type'] !== 'group' || $entry['company'] === '') {
				continue;
			}

			$company = $entry['company'];
			$existingLeaderContact = $this->findExistingLeaderContact($allCards, $company);
			if ($existingLeaderContact !== null && ($entry['leader'] === null || $this->isGeneratedGroupLeaderUri($entry['leader']['uri']))) {
				if (!in_array((string)$existingLeaderContact['uri'], $knownUris, true)) {
					foreach ($allCards as $card) {
						if (($card['uri'] ?? '') !== $existingLeaderContact['uri'] || !isset($card['carddata']) || !is_string($card['carddata'])) {
							continue;
						}

						$vCard = $this->parseVCard($card['carddata']);
						if (!$vCard instanceof VCard) {
							break;
						}

						$categories = $this->extractCategories($vCard);
						if (!in_array(self::CONTACT_GROUP_NAME, $categories, true)) {
							$vCard->add('CATEGORIES', self::CONTACT_GROUP_NAME);
							$cardDavBackend->updateCard($addressBookId, (string)$card['uri'], $vCard->serialize());
						}

						$createdLeader = true;
						$knownUris[] = (string)$card['uri'];
						break;
					}
				}

				continue;
			}

			if ($entry['leader'] !== null || count($entry['members']) <= 1) {
				continue;
			}

			$leaderUri = $this->buildGroupLeaderContactUri($company);
			$leaderUid = $this->buildGroupLeaderContactUid($company);
			$vCard = new VCard();
			$this->applyManagedGroupLeaderData($vCard, $company, $leaderUid);
			$cardDavBackend->createCard($addressBookId, $leaderUri, $vCard->serialize());

			$this->logger->info('Created missing Team4All group leader contact in default contacts address book.', [
				'addressBookId' => $addressBookId,
				'company' => $company,
				'cardUri' => $leaderUri,
			]);
			$createdLeader = true;
		}

		return $createdLeader;
	}

	private function findExistingLeaderContact(array $cards, string $company): ?array {
		$candidates = [];

		foreach ($cards as $card) {
			if (!isset($card['carddata']) || !is_string($card['carddata'])) {
				continue;
			}

			$vCard = $this->parseVCard($card['carddata']);
			if (!$vCard instanceof VCard) {
				continue;
			}

			$rawName = isset($vCard->FN) ? trim((string)$vCard->FN->getValue()) : '';
			$companies = $this->extractCompanies($vCard);
			$displayCompany = $this->extractDisplayCompany($vCard);
			if (!in_array($company, $companies, true)) {
				continue;
			}

			$normalizedCompany = $company;
			$effectiveName = $rawName !== '' ? $rawName : $normalizedCompany;

			if (!$this->isLeaderContact($rawName, $effectiveName, $company)) {
				continue;
			}

			$candidate = $this->buildContactDataFromCard($vCard, (string)($card['uri'] ?? ''));
			$candidate['company'] = $normalizedCompany;
			$candidate['companyDisplay'] = $displayCompany;
			$candidate['companies'] = $companies;
			$candidates[] = $candidate;
		}

		return $this->pickPreferredLeaderCandidate($candidates);
	}

	private function buildManagedContactUid(IUser $user): string {
		return self::MANAGED_CONTACT_UID_PREFIX . $user->getUID() . '@team4all.local';
	}

	private function buildManagedContactUri(IUser $user): string {
		return self::MANAGED_CONTACT_URI_PREFIX . $user->getUID() . '.vcf';
	}

	private function buildGroupLeaderContactUid(string $company): string {
		return self::GROUP_LEADER_UID_PREFIX . sha1($this->normalizeComparableValue($company)) . '@team4all.local';
	}

	private function buildGroupLeaderContactUri(string $company): string {
		return self::GROUP_LEADER_URI_PREFIX . sha1($this->normalizeComparableValue($company)) . '.vcf';
	}

	private function applyManagedGroupLeaderData(VCard $vCard, string $company, string $uid): void {
		$vCard->UID = $uid;
		$vCard->FN = $company;
		$vCard->N = [$company, '', '', '', ''];
		$vCard->ORG = $company;
		$vCard->NOTE = 'Adresskarte automatisch durch Team4All angelegt.';
		$vCard->add('CATEGORIES', self::CONTACT_GROUP_NAME);
	}

	/**
	 * @param array<int, array<string, mixed>> $cards
	 * @return list<array<string, mixed>>
	 */
	private function collectTeam4AllContactsFromCards(array $cards): array {
		$contacts = [];

		foreach ($cards as $card) {
			if (!isset($card['carddata']) || !is_string($card['carddata'])) {
				continue;
			}

			$vCard = $this->parseVCard($card['carddata']);
			if (!$vCard instanceof VCard) {
				continue;
			}

			$categories = $this->extractCategories($vCard);
			if (!in_array(self::CONTACT_GROUP_NAME, $categories, true)) {
				continue;
			}

			$contacts[] = $this->buildContactDataFromCard($vCard, (string)($card['uri'] ?? ''));
		}

		return $contacts;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function buildContactDataFromCard(VCard $vCard, string $uri): array {
		$name = isset($vCard->FN) ? trim((string)$vCard->FN->getValue()) : '';
		$nameParts = $this->extractStructuredNameParts($vCard);
		$note = $this->extractNote($vCard);
		$uid = isset($vCard->UID) ? trim((string)$vCard->UID->getValue()) : '';
		$companies = $this->extractCompanies($vCard);
		$displayCompany = $this->extractDisplayCompany($vCard);
		$company = $companies[0] ?? '';
		$effectiveName = $name !== '' ? $name : $company;
		$emails = $this->extractEmailValues($vCard);
		$telephoneEntries = $this->extractTelephoneEntries($vCard);
		$telephones = array_map(
			static fn(array $entry): string => $entry['value'],
			$telephoneEntries
		);
		$displayParts = $this->buildEditableDisplayNameParts($name, $nameParts, $company);

		return [
			'name' => $effectiveName !== '' ? $effectiveName : '(Ohne Namen)',
			'rawName' => $name,
			'searchText' => $this->buildContactSearchText(
				$name,
				$this->composeStructuredName($nameParts),
				implode(' ', $companies),
				implode(' ', $emails),
				implode(' ', $telephones),
			),
			'note' => $note,
			'uid' => $uid,
			'email' => $emails[0] ?? '',
			'uri' => $uri,
			'company' => $company,
			'companyDisplay' => $displayCompany,
			'companies' => $companies,
			'prefix' => $displayParts['prefix'],
			'firstName' => $displayParts['firstName'],
			'lastName' => $displayParts['lastName'],
			'addressType' => $this->extractAddressType($vCard),
			'streetAddress' => $this->extractStreetAddress($vCard),
			'postalCode' => $this->extractPostalCode($vCard),
			'locality' => $this->extractLocality($vCard),
			'telephoneEntries' => $telephoneEntries,
			'telephones' => implode("\n", $telephones),
			'emails' => implode("\n", $emails),
			'contactGroups' => $this->extractVisibleContactGroups($vCard),
		];
	}

	private function applyEditableContactDetails(
		VCard $vCard,
		string $prefix,
		string $firstName,
		string $lastName,
		string $addressType,
		string $streetAddress,
		string $postalCode,
		string $locality,
		string $telephones,
		string $emails,
	): void {
		$composedName = trim(implode(' ', array_filter([$prefix, $firstName, $lastName], static fn(string $value): bool => $value !== '')));
		$existingCompany = $this->extractCompany($vCard);
		$existingFormattedName = isset($vCard->FN) ? trim((string)$vCard->FN->getValue()) : '';
		$formattedName = $composedName !== '' ? $composedName : ($existingCompany !== '' ? $existingCompany : $existingFormattedName);

		while (isset($vCard->FN)) {
			unset($vCard->FN);
		}
		if ($formattedName !== '') {
			$vCard->add('FN', $formattedName);
		}

		while (isset($vCard->N)) {
			unset($vCard->N);
		}
		$vCard->add('N', [$lastName, $firstName, '', $prefix, '']);

		$addressType = $this->normalizeAddressType($addressType);
		$this->removeAddressPropertiesByType($vCard, $addressType);
		if ($streetAddress !== '' || $postalCode !== '' || $locality !== '') {
			$vCard->add('ADR', ['', '', $streetAddress, $locality, '', $postalCode, ''], ['TYPE' => strtoupper($addressType)]);
		}

		$this->removeProperties($vCard, 'TEL');
		foreach ($this->splitMultilineValues($telephones) as $telephone) {
			$vCard->add('TEL', $telephone);
		}

		$this->removeProperties($vCard, 'EMAIL');
		foreach ($this->splitMultilineValues($emails) as $email) {
			$vCard->add('EMAIL', $email, ['TYPE' => 'INTERNET']);
		}
	}

	private function removeProperties(VCard $vCard, string $propertyName): void {
		while (isset($vCard->$propertyName)) {
			unset($vCard->$propertyName);
		}
	}

	/**
	 * @return array{type:string,value:array{0:string,1:string,2:string,3:string,4:string,5:string,6:string}}
	 */
	private function getPreferredAddress(VCard $vCard): array {
		$homeAddress = null;
		$otherAddress = null;

		foreach ($vCard->select('ADR') as $addressProperty) {
			$type = $this->getAddressType($addressProperty);
			$value = $this->normalizeAddressValue($addressProperty->getValue());

			if ($type === 'work') {
				return [
					'type' => 'work',
					'value' => $value,
				];
			}

			if ($homeAddress === null && $type === 'home') {
				$homeAddress = [
					'type' => 'home',
					'value' => $value,
				];
				continue;
			}

			if ($otherAddress === null && $type === 'other') {
				$otherAddress = [
					'type' => 'other',
					'value' => $value,
				];
			}
		}

		return $homeAddress
			?? $otherAddress
			?? [
				'type' => 'work',
				'value' => ['', '', '', '', '', '', ''],
			];
	}

	/**
	 * @return array{0:string,1:string,2:string,3:string,4:string,5:string,6:string}
	 */
	private function getPreferredAddressValue(VCard $vCard): array {
		return $this->getPreferredAddress($vCard)['value'];
	}

	/**
	 * @param mixed $value
	 * @return array{0:string,1:string,2:string,3:string,4:string,5:string,6:string}
	 */
	private function normalizeAddressValue(mixed $value): array {
		if (is_array($value)) {
			$parts = $value;
		} else {
			$parts = preg_split('/(?<!\\\\);/', (string)$value) ?: [];
		}

		$parts = array_pad($parts, 7, '');

		return [
			$this->sanitizeVCardDisplayValue((string)($parts[0] ?? '')),
			$this->sanitizeVCardDisplayValue((string)($parts[1] ?? '')),
			$this->sanitizeVCardDisplayValue((string)($parts[2] ?? '')),
			$this->sanitizeVCardDisplayValue((string)($parts[3] ?? '')),
			$this->sanitizeVCardDisplayValue((string)($parts[4] ?? '')),
			$this->sanitizeVCardDisplayValue((string)($parts[5] ?? '')),
			$this->sanitizeVCardDisplayValue((string)($parts[6] ?? '')),
		];
	}

	private function removeAddressPropertiesByType(VCard $vCard, string $addressType): void {
		foreach ($vCard->select('ADR') as $addressProperty) {
			if ($this->getAddressType($addressProperty) === $addressType) {
				unset($addressProperty);
			}
		}
	}

	private function isWorkAddressProperty($addressProperty): bool {
		return $this->getAddressType($addressProperty) === 'work';
	}

	private function isHomeAddressProperty($addressProperty): bool {
		return $this->getAddressType($addressProperty) === 'home';
	}

	private function isOtherAddressProperty($addressProperty): bool {
		return $this->getAddressType($addressProperty) === 'other';
	}

	private function getAddressType($addressProperty): string {
		$typeParts = $this->getAddressTypeParts($addressProperty);

		if (in_array('work', $typeParts, true)) {
			return 'work';
		}

		if (in_array('home', $typeParts, true)) {
			return 'home';
		}

		if (in_array('other', $typeParts, true)) {
			return 'other';
		}

		return 'work';
	}

	private function normalizeAddressType(string $addressType): string {
		$addressType = mb_strtolower(trim($addressType));

		return match ($addressType) {
			'home', 'other' => $addressType,
			default => 'work',
		};
	}

	/**
	 * @return list<string>
	 */
	private function getAddressTypeParts($addressProperty): array {
		if (!isset($addressProperty['TYPE'])) {
			return [];
		}

		$typeValue = (string)$addressProperty['TYPE']->getValue();
		return array_map(
			static fn(string $part): string => mb_strtolower(trim($part)),
			explode(',', $typeValue)
		);
	}

	/**
	 * @return list<string>
	 */
	private function splitMultilineValues(string $value): array {
		$lines = preg_split('/\r\n|\r|\n/', $value) ?: [];

		return array_values(array_filter(array_map(
			static fn(string $line): string => trim($line),
			$lines
		), static fn(string $line): bool => $line !== ''));
	}

	private function isLeaderContact(string $rawName, string $effectiveName, string $company): bool {
		$normalizedCompany = $this->normalizeComparableValue($company);

		if ($normalizedCompany === '') {
			return false;
		}

		if ($this->normalizeComparableValue($rawName) === $normalizedCompany) {
			return true;
		}

		return $this->normalizeComparableValue($effectiveName) === $normalizedCompany;
	}

	private function normalizeComparableValue(string $value): string {
		$value = str_replace(
			['\\,', '\\;', '\\\\'],
			[',', ' ', '\\'],
			$value
		);
		$value = str_replace(';', ' ', $value);
		$value = mb_strtolower(trim($value, " \t\n\r\0\x0B;"));
		$value = preg_replace('/\s+/', ' ', $value) ?? $value;

		return $value;
	}

	private function cardContainsTeam4AllCategory(string $cardData): bool {
		$vCard = $this->parseVCard($cardData);
		if (!$vCard instanceof VCard) {
			$this->logger->warning('Could not inspect Team4All category state because the provisioning vCard could not be parsed.');
			return false;
		}

		$categories = $this->extractCategories($vCard);
		$hasCategory = in_array(self::CONTACT_GROUP_NAME, $categories, true);

		$this->logger->info('Inspected Team4All category state on provisioning contact.', [
			'hasTeam4AllCategory' => $hasCategory,
			'categories' => $categories,
		]);

		return $hasCategory;
	}

	private function parseVCard(string $cardData): ?VCard {
		try {
			$vCard = Reader::read($cardData);
		} catch (Throwable) {
			return null;
		}

		return $vCard instanceof VCard ? $vCard : null;
	}
}
