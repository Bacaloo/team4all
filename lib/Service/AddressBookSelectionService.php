<?php

declare(strict_types=1);

namespace OCA\Team4All\Service;

use OCA\Team4All\AppInfo\Application;
use OCP\IConfig;

class AddressBookSelectionService {
	private const CONFIG_KEY = 'allowed_address_books_by_user';
	private const DEFAULT_CONFIG_KEY = 'default_address_book_by_user';
	private const LEGACY_CONFIG_KEY = 'allowed_address_books';
	private const LEGACY_DEFAULT_CONFIG_KEY = 'default_address_book';

	public function __construct(
		private IConfig $config,
	) {
	}

	/**
	 * @return array<string, list<string>>
	 */
	public function getSelectedAddressBookIdsByUser(): array {
		$decoded = $this->decodeMapConfig(self::CONFIG_KEY);
		$normalized = [];

		foreach ($decoded as $userUid => $addressBookIds) {
			$userUid = $this->normalizeUserUid($userUid);
			if ($userUid === '' || !is_array($addressBookIds)) {
				continue;
			}

			$normalized[$userUid] = $this->normalizeAddressBookIds($addressBookIds);
		}

		return $normalized;
	}

	/**
	 * @return list<string>
	 */
	public function getSelectedAddressBookIds(?string $userUid = null): array {
		$userUid = $this->normalizeUserUid($userUid);
		$selectedByUser = $this->getSelectedAddressBookIdsByUser();
		if ($userUid !== '' && array_key_exists($userUid, $selectedByUser)) {
			return $selectedByUser[$userUid];
		}

		return $this->getLegacySelectedAddressBookIds();
	}

	/**
	 * @param list<string> $addressBookIds
	 */
	public function saveSelectedAddressBookIdsForUser(string $userUid, array $addressBookIds): void {
		$userUid = $this->normalizeUserUid($userUid);
		if ($userUid === '') {
			return;
		}

		$selectedByUser = $this->getSelectedAddressBookIdsByUser();
		$selectedByUser[$userUid] = $this->normalizeAddressBookIds($addressBookIds);
		$this->saveMapConfig(self::CONFIG_KEY, $selectedByUser);

		$defaultAddressBookId = $this->getDefaultAddressBookId($userUid);
		if ($defaultAddressBookId !== '' && !in_array($defaultAddressBookId, $selectedByUser[$userUid], true)) {
			$this->clearDefaultAddressBookIdForUser($userUid);
		}
	}

	/**
	 * @return array<string, string>
	 */
	public function getDefaultAddressBookIdsByUser(): array {
		$decoded = $this->decodeMapConfig(self::DEFAULT_CONFIG_KEY);
		$normalized = [];

		foreach ($decoded as $userUid => $addressBookId) {
			$userUid = $this->normalizeUserUid($userUid);
			if ($userUid === '' || !is_string($addressBookId)) {
				continue;
			}

			$addressBookId = trim($addressBookId);
			if ($addressBookId === '') {
				continue;
			}

			$normalized[$userUid] = $addressBookId;
		}

		return $normalized;
	}

	public function getDefaultAddressBookId(?string $userUid = null): string {
		$userUid = $this->normalizeUserUid($userUid);
		$defaultByUser = $this->getDefaultAddressBookIdsByUser();
		if ($userUid !== '' && array_key_exists($userUid, $defaultByUser)) {
			return $defaultByUser[$userUid];
		}

		return trim($this->config->getAppValue(Application::APP_ID, self::LEGACY_DEFAULT_CONFIG_KEY, ''));
	}

	public function saveDefaultAddressBookIdForUser(string $userUid, string $addressBookId): void {
		$userUid = $this->normalizeUserUid($userUid);
		if ($userUid === '') {
			return;
		}

		$addressBookId = trim($addressBookId);
		if ($addressBookId === '') {
			$this->clearDefaultAddressBookIdForUser($userUid);
			return;
		}

		$defaultByUser = $this->getDefaultAddressBookIdsByUser();
		$defaultByUser[$userUid] = $addressBookId;
		$this->saveMapConfig(self::DEFAULT_CONFIG_KEY, $defaultByUser);
	}

	public function clearDefaultAddressBookIdForUser(string $userUid): void {
		$userUid = $this->normalizeUserUid($userUid);
		if ($userUid === '') {
			return;
		}

		$defaultByUser = $this->getDefaultAddressBookIdsByUser();
		unset($defaultByUser[$userUid]);
		$this->saveMapConfig(self::DEFAULT_CONFIG_KEY, $defaultByUser);
	}

	/**
	 * @return list<string>
	 */
	private function getLegacySelectedAddressBookIds(): array {
		$rawValue = $this->config->getAppValue(Application::APP_ID, self::LEGACY_CONFIG_KEY, '[]');
		$decoded = json_decode($rawValue, true);
		if (!is_array($decoded)) {
			return [];
		}

		return $this->normalizeAddressBookIds($decoded);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function decodeMapConfig(string $configKey): array {
		$rawValue = $this->config->getAppValue(Application::APP_ID, $configKey, '{}');
		$decoded = json_decode($rawValue, true);

		return is_array($decoded) ? $decoded : [];
	}

	/**
	 * @param array<string, mixed> $values
	 */
	private function saveMapConfig(string $configKey, array $values): void {
		$this->config->setAppValue(
			Application::APP_ID,
			$configKey,
			json_encode($values, JSON_THROW_ON_ERROR),
		);
	}

	private function normalizeUserUid(?string $userUid): string {
		return trim((string)$userUid);
	}

	/**
	 * @param array<int, mixed> $addressBookIds
	 * @return list<string>
	 */
	private function normalizeAddressBookIds(array $addressBookIds): array {
		$normalized = [];

		foreach ($addressBookIds as $addressBookId) {
			if (!is_string($addressBookId)) {
				continue;
			}

			$addressBookId = trim($addressBookId);
			if ($addressBookId === '') {
				continue;
			}

			$normalized[$addressBookId] = $addressBookId;
		}

		return array_values($normalized);
	}
}
