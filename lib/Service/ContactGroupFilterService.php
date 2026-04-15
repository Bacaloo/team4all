<?php

declare(strict_types=1);

namespace OCA\Team4All\Service;

use OCA\Team4All\AppInfo\Application;
use OCP\IConfig;

class ContactGroupFilterService {
	private const CONFIG_KEY = 'frontend_filter_contact_groups';

	public function __construct(
		private IConfig $config,
	) {
	}

	/**
	 * @return list<string>
	 */
	public function getSelectedFrontendFilterGroups(): array {
		$rawValue = $this->config->getAppValue(Application::APP_ID, self::CONFIG_KEY, '[]');
		$decoded = json_decode($rawValue, true);
		if (!is_array($decoded)) {
			return [];
		}

		return $this->normalizeGroupNames($decoded);
	}

	/**
	 * @param list<string> $groupNames
	 */
	public function saveSelectedFrontendFilterGroups(array $groupNames): void {
		$this->config->setAppValue(
			Application::APP_ID,
			self::CONFIG_KEY,
			json_encode($this->normalizeGroupNames($groupNames), JSON_THROW_ON_ERROR),
		);
	}

	/**
	 * @param array<int, mixed> $groupNames
	 * @return list<string>
	 */
	private function normalizeGroupNames(array $groupNames): array {
		$normalized = [];

		foreach ($groupNames as $groupName) {
			if (!is_string($groupName)) {
				continue;
			}

			$groupName = trim($groupName);
			if ($groupName === '') {
				continue;
			}

			$normalized[mb_strtolower($groupName)] = $groupName;
		}

		natcasesort($normalized);

		return array_values($normalized);
	}
}
