<?php

declare(strict_types=1);

namespace OCA\Team4All\Db;

use DateTime;
use OCP\AppFramework\Db\Entity;

class DocumentReference extends Entity {
	protected string $contactUid = '';
	protected string $fileName = '';
	protected ?string $subject = null;
	protected ?DateTime $documentCreatedAt = null;

	public function __construct() {
		$this->addType('id', 'integer');
		$this->addType('documentCreatedAt', 'datetime');
	}
}
