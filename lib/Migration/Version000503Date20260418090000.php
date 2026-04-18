<?php

declare(strict_types=1);

namespace OCA\Team4All\Migration;

use Closure;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000503Date20260418090000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var Schema $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('team4all_document_reference')) {
			return $schema;
		}

		$table = $schema->createTable('team4all_document_reference');
		$table->addColumn('id', Types::INTEGER, [
			'autoincrement' => true,
			'notnull' => true,
			'unsigned' => true,
		]);
		$table->addColumn('contact_uid', Types::STRING, [
			'length' => 255,
			'notnull' => true,
		]);
		$table->addColumn('file_name', Types::STRING, [
			'length' => 255,
			'notnull' => true,
		]);
		$table->addColumn('subject', Types::STRING, [
			'length' => 1024,
			'notnull' => false,
		]);
		$table->addColumn('document_created_at', Types::DATETIME_MUTABLE, [
			'notnull' => false,
		]);
		$table->setPrimaryKey(['id']);
		$table->addUniqueIndex(['contact_uid', 'file_name'], 't4a_doc_ref_uid_file_uidx');
		$table->addIndex(['contact_uid'], 't4a_doc_ref_contact_uidx');
		$table->addIndex(['document_created_at'], 't4a_doc_ref_created_at_idx');

		return $schema;
	}
}
