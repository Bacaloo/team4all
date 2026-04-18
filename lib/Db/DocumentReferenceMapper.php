<?php

declare(strict_types=1);

namespace OCA\Team4All\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @extends QBMapper<DocumentReference>
 */
class DocumentReferenceMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'team4all_document_reference', DocumentReference::class);
	}

	/**
	 * @param list<string> $contactUids
	 * @return list<DocumentReference>
	 */
	public function findByContactUids(array $contactUids): array {
		$contactUids = array_values(array_filter(array_map('trim', $contactUids), static fn(string $uid): bool => $uid !== ''));
		if ($contactUids === []) {
			return [];
		}

		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->in('contact_uid', $qb->createNamedParameter($contactUids, IQueryBuilder::PARAM_STR_ARRAY)))
			->orderBy('document_created_at', 'DESC')
			->addOrderBy('subject', 'ASC')
			->addOrderBy('file_name', 'ASC');

		return $this->findEntities($qb);
	}

	/**
	 * @return list<DocumentReference>
	 */
	public function findUnassigned(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('contact_uid', $qb->createNamedParameter('')))
			->orderBy('file_name', 'ASC');

		return $this->findEntities($qb);
	}
}
