<?php

declare(strict_types=1);

namespace OCA\Team4All\Service;

use OCA\Team4All\AppInfo\Application;
use OCP\IConfig;

class AddressBookSelectionService {
	private const CONFIG_KEY = 'allowed_address_books';
	private const DEFAULT_CONFIG_KEY = 'default_address_book';

	public function __construct(
		private IConfig $config,
	) {
	}

	/**
	 * @return list<string>
	 */
	public function getSelectedAddressBookIds(): array {
		$rawValue = $this->config->getAppValue(Application::APP_ID, self::CONFIG_KEY, '[]');
		$decoded = json_decode($rawValue, true);
		if (!is_array($decoded)) {
			return [];
		}

		return $this->normalizeAddressBookIds($decoded);
	}

	/**
	 * @param list<string> $addressBookIds
	 */
	public function saveSelectedAddressBookIds(array $addressBookIds): void {
		$normalizedIds = $this->normalizeAddressBookIds($addressBookIds);
		$this->config->setAppValue(
			Application::APP_ID,
			self::CONFIG_KEY,
			json_encode($normalizedIds, JSON_THROW_ON_ERROR),
		);

		$defaultAddressBookId = $this->getDefaultAddressBookId();
		if ($defaultAddressBookId !== '' && !in_array($defaultAddressBookId, $normalizedIds, true)) {
			$this->clearDefaultAddressBookId();
		}
	}

	public function getDefaultAddressBookId(): string {
		return trim($this->config->getAppValue(Application::APP_ID, self::DEFAULT_CONFIG_KEY, ''));
	}

	public function saveDefaultAddressBookId(string $addressBookId): void {
		$addressBookId = trim($addressBookId);
		if ($addressBookId === '') {
			$this->clearDefaultAddressBookId();
			return;
		}

		$this->config->setAppValue(Application::APP_ID, self::DEFAULT_CONFIG_KEY, $addressBookId);
	}

	public function clearDefaultAddressBookId(): void {
		$this->config->deleteAppValue(Application::APP_ID, self::DEFAULT_CONFIG_KEY);
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
