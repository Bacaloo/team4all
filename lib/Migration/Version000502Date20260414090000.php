<?php

declare(strict_types=1);

namespace OCA\Team4All\Migration;

use Closure;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000502Date20260414090000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var Schema $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('team4all_contact_meta')) {
			return $schema;
		}

		$table = $schema->createTable('team4all_contact_meta');
		$table->addColumn('id', Types::INTEGER, [
			'autoincrement' => true,
			'notnull' => true,
			'unsigned' => true,
		]);
		$table->addColumn('nc_user_id', Types::STRING, [
			'length' => 64,
			'notnull' => true,
		]);
		$table->addColumn('contact_uid', Types::STRING, [
			'length' => 255,
			'notnull' => true,
		]);
		$table->addColumn('anrede', Types::STRING, [
			'length' => 255,
			'notnull' => false,
		]);
		$table->addColumn('briefanrede', Types::TEXT, [
			'notnull' => false,
		]);
		$table->addColumn('created_at', Types::DATETIME_MUTABLE, [
			'notnull' => true,
		]);
		$table->addColumn('updated_at', Types::DATETIME_MUTABLE, [
			'notnull' => true,
		]);
		$table->setPrimaryKey(['id']);
		$table->addUniqueIndex(['nc_user_id', 'contact_uid'], 't4a_contact_meta_user_uidx');
		$table->addIndex(['contact_uid'], 't4a_contact_meta_contact_uidx');

		return $schema;
	}
}
