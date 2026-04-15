<?php

declare(strict_types=1);

namespace OCA\Team4All\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class AdminSection implements IIconSection {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getID(): string {
		return 'team4all';
	}

	public function getName(): string {
		return $this->l10n->t('Team4All');
	}

	public function getPriority(): int {
		return 85;
	}

	public function getIcon(): string {
		return $this->urlGenerator->imagePath('team4all', 'app.svg');
	}
}
