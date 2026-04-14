<?php

declare(strict_types=1);

namespace OCA\Team4All\Db;

use DateTime;
use OCP\AppFramework\Db\Entity;

class ContactMeta extends Entity {
	protected string $ncUserId = '';
	protected string $contactUid = '';
	protected ?string $anrede = null;
	protected ?string $briefanrede = null;
	protected ?DateTime $createdAt = null;
	protected ?DateTime $updatedAt = null;

	public function __construct() {
		$this->addType('id', 'integer');
		$this->addType('createdAt', 'datetime');
		$this->addType('updatedAt', 'datetime');
	}
}
