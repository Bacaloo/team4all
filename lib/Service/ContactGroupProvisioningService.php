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

	/**
	 * @return list<array{
	 *   type: 'group'|'person',
	 *   label: string,
	 *   company: string,
	 *   leader: array{name: string, rawName: string, searchText: string, note: string, uid: string, email: string, uri: string, company: string}|null,
	 *   members: list<array{name: string, rawName: string, searchText: string, note: string, uid: string, email: string, uri: string, company: string}>,
	 *   person: array{name: string, rawName: string, searchText: string, note: string, uid: string, email: string, uri: string, company: string}|null
	 * }>
	 */
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
			$structuredName = $this->extractStructuredName($vCard);
			$email = '';
			$note = $this->extractNote($vCard);
			$uid = isset($vCard->UID) ? trim((string)$vCard->UID->getValue()) : '';
			$telephone = '';
			$company = $this->extractCompany($vCard);
			$effectiveName = $name !== '' ? $name : $company;

			foreach ($vCard->select('EMAIL') as $emailProperty) {
				$email = trim((string)$emailProperty->getValue());
				if ($email !== '') {
					break;
				}
			}

			foreach ($vCard->select('TEL') as $telephoneProperty) {
				$telephone = trim((string)$telephoneProperty->getValue());
				if ($telephone !== '') {
					break;
				}
			}

			$contacts[] = [
				'name' => $effectiveName !== '' ? $effectiveName : '(Ohne Namen)',
				'rawName' => $name,
				'searchText' => $this->buildContactSearchText($name, $structuredName, $company, $email, $telephone),
				'note' => $note,
				'uid' => $uid,
				'email' => $email,
				'uri' => (string)($card['uri'] ?? ''),
				'company' => $company,
			];
		}

		$contacts = $this->ensureMissingGroupLeaderContacts(
			$cardDavBackend,
			(int)$addressBook['id'],
			$contacts,
		);

		return $this->groupContactsByCompany($contacts);
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

	private function extractStructuredName(VCard $vCard): string {
		if (!isset($vCard->N)) {
			return '';
		}

		$value = $vCard->N->getValue();
		if (!is_array($value)) {
			return trim((string)$value);
		}

		$parts = [];
		foreach ($value as $part) {
			if (is_array($part)) {
				foreach ($part as $nestedPart) {
					$nestedPart = trim((string)$nestedPart);
					if ($nestedPart !== '') {
						$parts[] = $nestedPart;
					}
				}
				continue;
			}

			$part = trim((string)$part);
			if ($part !== '') {
				$parts[] = $part;
			}
		}

		return implode(' ', $parts);
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
	 * @param list<array{name: string, rawName: string, searchText: string, note: string, uid: string, email: string, uri: string, company: string}> $contacts
	 * @return list<array{
	 *   type: 'group'|'person',
	 *   label: string,
	 *   company: string,
	 *   leader: array{name: string, rawName: string, searchText: string, note: string, uid: string, email: string, uri: string, company: string}|null,
	 *   members: list<array{name: string, rawName: string, searchText: string, note: string, uid: string, email: string, uri: string, company: string}>,
	 *   person: array{name: string, rawName: string, searchText: string, note: string, uid: string, email: string, uri: string, company: string}|null
	 * }>
	 */
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

	/**
	 * @param list<array{name: string, rawName: string, searchText: string, note: string, uid: string, email: string, uri: string, company: string}> $candidates
	 * @return array{name: string, rawName: string, searchText: string, note: string, uid: string, email: string, uri: string, company: string}|null
	 */
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

	/**
	 * @param array{name: string, rawName: string, searchText: string, note: string, uid: string, email: string, uri: string, company: string} $candidate
	 */
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

	/**
	 * @param list<array{name: string, rawName: string, searchText: string, note: string, uid: string, email: string, uri: string, company: string}> $contacts
	 * @return list<array{name: string, rawName: string, searchText: string, note: string, uid: string, email: string, uri: string, company: string}>
	 */
	private function ensureMissingGroupLeaderContacts(object $cardDavBackend, int $addressBookId, array $contacts): array {
		$entries = $this->groupContactsByCompany($contacts);
		$allCards = $cardDavBackend->getCards($addressBookId);

		foreach ($entries as $entry) {
			if ($entry['type'] !== 'group' || $entry['company'] === '') {
				continue;
			}

			if ($entry['leader'] !== null || count($entry['members']) <= 1) {
				continue;
			}

			$company = $entry['company'];
			$existingLeaderContact = $this->findExistingLeaderContact($allCards, $company);
			if ($existingLeaderContact !== null) {
				$contacts[] = $existingLeaderContact;
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
			];
		}

		return $contacts;
	}

	/**
	 * @param array<int, array<string, mixed>> $cards
	 * @return array{name: string, rawName: string, searchText: string, note: string, uid: string, email: string, uri: string, company: string}|null
	 */
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

			$email = '';
			$telephone = '';
			foreach ($vCard->select('EMAIL') as $emailProperty) {
				$email = trim((string)$emailProperty->getValue());
				if ($email !== '') {
					break;
				}
			}

			foreach ($vCard->select('TEL') as $telephoneProperty) {
				$telephone = trim((string)$telephoneProperty->getValue());
				if ($telephone !== '') {
					break;
				}
			}

			$candidates[] = [
				'name' => $effectiveName !== '' ? $effectiveName : '(Ohne Namen)',
				'rawName' => $rawName,
				'searchText' => $this->buildContactSearchText($rawName, $this->extractStructuredName($vCard), $normalizedCompany, $email, $telephone),
				'note' => $this->extractNote($vCard),
				'uid' => isset($vCard->UID) ? trim((string)$vCard->UID->getValue()) : '',
				'email' => $email,
				'uri' => (string)($card['uri'] ?? ''),
				'company' => $normalizedCompany,
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
		$vCard->ORG = $company;
		$vCard->add('CATEGORIES', self::CONTACT_GROUP_NAME);
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
