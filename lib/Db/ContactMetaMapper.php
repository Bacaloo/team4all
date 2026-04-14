<?php

declare(strict_types=1);

namespace OCA\Team4All\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @extends QBMapper<ContactMeta>
 */
class ContactMetaMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'team4all_contact_meta', ContactMeta::class);
	}

	public function findOneByNcUserIdAndContactUid(string $ncUserId, string $contactUid): ?ContactMeta {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('nc_user_id', $qb->createNamedParameter($ncUserId)))
			->andWhere($qb->expr()->eq('contact_uid', $qb->createNamedParameter($contactUid)));

		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException|MultipleObjectsReturnedException) {
			return null;
		}
	}
}
