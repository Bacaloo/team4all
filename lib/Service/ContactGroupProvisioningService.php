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

		$contacts = [];
		$cards = $cardDavBackend->getCards((int)$addressBook['id']);

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

			$name = isset($vCard->FN) ? trim((string)$vCard->FN->getValue()) : '';
			$nameParts = $this->extractStructuredNameParts($vCard);
			$note = $this->extractNote($vCard);
			$uid = isset($vCard->UID) ? trim((string)$vCard->UID->getValue()) : '';
			$company = $this->extractCompany($vCard);
			$effectiveName = $name !== '' ? $name : $company;
			$emails = $this->extractEmailValues($vCard);
			$telephones = $this->extractTelephoneValues($vCard);
			$displayParts = $this->buildEditableDisplayNameParts($name, $nameParts, $company);

			$contacts[] = [
				'name' => $effectiveName !== '' ? $effectiveName : '(Ohne Namen)',
				'rawName' => $name,
				'searchText' => $this->buildContactSearchText(
					$name,
					$this->composeStructuredName($nameParts),
					$company,
					implode(' ', $emails),
					implode(' ', $telephones),
				),
				'note' => $note,
				'uid' => $uid,
				'email' => $emails[0] ?? '',
				'uri' => (string)($card['uri'] ?? ''),
				'company' => $company,
				'prefix' => $displayParts['prefix'],
				'firstName' => $displayParts['firstName'],
				'lastName' => $displayParts['lastName'],
				'address' => $this->extractAddress($vCard),
				'telephones' => implode("\n", $telephones),
				'emails' => implode("\n", $emails),
			];
		}

		$contacts = $this->ensureMissingGroupLeaderContacts(
			$cardDavBackend,
			(int)$addressBook['id'],
			$contacts,
		);

		return $this->groupContactsByCompany($contacts);
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
		string $address,
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
				$address,
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

	private function extractCompany(VCard $vCard): string {
		if (!isset($vCard->ORG)) {
			return '';
		}

		$value = $vCard->ORG->getValue();
		if (is_array($value)) {
			$value = $value[0] ?? '';
		}

		return $this->canonicalizeCompanyName((string)$value);
	}

	private function canonicalizeCompanyName(string $value): string {
		$value = trim($value);
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
			return [
				'lastName' => trim((string)$value),
				'firstName' => '',
				'additional' => '',
				'prefix' => '',
				'suffix' => '',
			];
		}

		return [
			'lastName' => trim((string)($value[0] ?? '')),
			'firstName' => trim((string)($value[1] ?? '')),
			'additional' => trim((string)($value[2] ?? '')),
			'prefix' => trim((string)($value[3] ?? '')),
			'suffix' => trim((string)($value[4] ?? '')),
		];
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
	 * @return list<string>
	 */
	private function extractTelephoneValues(VCard $vCard): array {
		$telephones = [];

		foreach ($vCard->select('TEL') as $telephoneProperty) {
			$telephone = trim((string)$telephoneProperty->getValue());
			if ($telephone !== '') {
				$telephones[] = $telephone;
			}
		}

		return array_values(array_unique($telephones));
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

	private function extractAddress(VCard $vCard): string {
		foreach ($vCard->select('ADR') as $addressProperty) {
			$value = $addressProperty->getValue();
			if (!is_array($value)) {
				$address = trim((string)$value);
				if ($address !== '') {
					return $address;
				}
				continue;
			}

			$streetLines = array_values(array_filter([
				trim((string)($value[0] ?? '')),
				trim((string)($value[1] ?? '')),
				trim((string)($value[2] ?? '')),
			], static fn(string $part): bool => $part !== ''));
			$locality = trim((string)($value[3] ?? ''));
			$region = trim((string)($value[4] ?? ''));
			$postalCode = trim((string)($value[5] ?? ''));
			$country = trim((string)($value[6] ?? ''));
			$cityLine = trim(implode(' ', array_filter([$postalCode, $locality])));

			$lines = array_values(array_filter([
				...$streetLines,
				$cityLine,
				$region,
				$country,
			], static fn(string $part): bool => $part !== ''));

			if ($lines !== []) {
				return implode("\n", $lines);
			}
		}

		return '';
	}

	private function groupContactsByCompany(array $contacts): array {
		$grouped = [];
		$entries = [];

		foreach ($contacts as $contact) {
			$company = trim($contact['company']);
			if ($company === '') {
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

	private function ensureMissingGroupLeaderContacts(object $cardDavBackend, int $addressBookId, array $contacts): array {
		$entries = $this->groupContactsByCompany($contacts);
		$allCards = $cardDavBackend->getCards($addressBookId);

		foreach ($entries as $entry) {
			if ($entry['type'] !== 'group' || $entry['company'] === '') {
				continue;
			}

			$company = $entry['company'];
			$existingLeaderContact = $this->findExistingLeaderContact($allCards, $company);
			if ($existingLeaderContact !== null && ($entry['leader'] === null || $this->isGeneratedGroupLeaderUri($entry['leader']['uri']))) {
				$contacts[] = $existingLeaderContact;
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

			$contacts[] = [
				'name' => $company,
				'rawName' => $company,
				'searchText' => $company,
				'note' => '',
				'uid' => $leaderUid,
				'email' => '',
				'uri' => $leaderUri,
				'company' => $company,
				'prefix' => '',
				'firstName' => '',
				'lastName' => $company,
				'address' => '',
				'telephones' => '',
				'emails' => '',
			];
		}

		return $contacts;
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
			$normalizedCompany = $this->extractCompany($vCard);
			$effectiveName = $rawName !== '' ? $rawName : $normalizedCompany;

			if (!$this->isLeaderContact($rawName, $effectiveName, $company)) {
				continue;
			}

			$nameParts = $this->extractStructuredNameParts($vCard);
			$emails = $this->extractEmailValues($vCard);
			$telephones = $this->extractTelephoneValues($vCard);
			$displayParts = $this->buildEditableDisplayNameParts($rawName, $nameParts, $normalizedCompany);

			$candidates[] = [
				'name' => $effectiveName !== '' ? $effectiveName : '(Ohne Namen)',
				'rawName' => $rawName,
				'searchText' => $this->buildContactSearchText(
					$rawName,
					$this->composeStructuredName($nameParts),
					$normalizedCompany,
					implode(' ', $emails),
					implode(' ', $telephones),
				),
				'note' => $this->extractNote($vCard),
				'uid' => isset($vCard->UID) ? trim((string)$vCard->UID->getValue()) : '',
				'email' => $emails[0] ?? '',
				'uri' => (string)($card['uri'] ?? ''),
				'company' => $normalizedCompany,
				'prefix' => $displayParts['prefix'],
				'firstName' => $displayParts['firstName'],
				'lastName' => $displayParts['lastName'],
				'address' => $this->extractAddress($vCard),
				'telephones' => implode("\n", $telephones),
				'emails' => implode("\n", $emails),
			];
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
		$vCard->N = ['', $company, '', '', ''];
		$vCard->ORG = $company;
		$vCard->NOTE = 'Adresskarte automatisch durch Team4All angelegt.';
		$vCard->add('CATEGORIES', self::CONTACT_GROUP_NAME);
	}

	private function applyEditableContactDetails(
		VCard $vCard,
		string $prefix,
		string $firstName,
		string $lastName,
		string $address,
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

		foreach ($vCard->select('ADR') as $addressProperty) {
			unset($addressProperty);
		}
		$this->removeProperties($vCard, 'ADR');
		$normalizedAddress = trim(str_replace("\r\n", "\n", $address));
		if ($normalizedAddress !== '') {
			$vCard->add('ADR', $this->buildAddressValue($normalizedAddress));
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
	 * @return list<string>
	 */
	private function splitMultilineValues(string $value): array {
		$lines = preg_split('/\r\n|\r|\n/', $value) ?: [];

		return array_values(array_filter(array_map(
			static fn(string $line): string => trim($line),
			$lines
		), static fn(string $line): bool => $line !== ''));
	}

	/**
	 * @return array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string}
	 */
	private function buildAddressValue(string $address): array {
		$lines = $this->splitMultilineValues($address);
		$street = $lines[0] ?? '';
		$locality = '';
		$region = '';
		$postalCode = '';
		$country = '';

		if (isset($lines[1]) && preg_match('/^(\S+)\s+(.+)$/u', $lines[1], $matches) === 1) {
			$postalCode = trim($matches[1]);
			$locality = trim($matches[2]);
		} elseif (isset($lines[1])) {
			$locality = $lines[1];
		}

		if (isset($lines[2])) {
			$region = $lines[2];
		}

		if (isset($lines[3])) {
			$country = $lines[3];
		}

		if (count($lines) > 4) {
			$street = implode("\n", array_slice($lines, 0, count($lines) - 3));
			if (preg_match('/^(\S+)\s+(.+)$/u', $lines[count($lines) - 3], $matches) === 1) {
				$postalCode = trim($matches[1]);
				$locality = trim($matches[2]);
			}
			$region = $lines[count($lines) - 2];
			$country = $lines[count($lines) - 1];
		}

		return ['', '', $street, $locality, $region, $postalCode, $country];
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
			[',', ';', '\\'],
			$value
		);
		$value = mb_strtolower(trim($value));
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
