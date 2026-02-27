<?php

declare(strict_types=1);

namespace OCA\TalkWebhooks\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * @psalm-suppress UnusedClass Loaded via migrations
 */
final class Version1Initial extends SimpleMigrationStep {
	/**
	 * @psalm-suppress UndefinedDocblockClass Doctrine\DBAL\Schema\Table not available
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable('talk_webhooks')) {
			$table = $schema->createTable('talk_webhooks');

			$table->addColumn('id', Types::INTEGER, [
				'notnull' => true,
				'autoincrement' => true,
			]);
			$table->addColumn('channel_token', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('name', Types::STRING, [
				'notnull' => true,
				'length' => 128,
				'default' => '',
			]);
			$table->addColumn('secret_hash', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('created_at', Types::INTEGER, [
				'notnull' => true,
			]);

			$table->setPrimaryKey(['id']);
			$table->addIndex(['channel_token'], 'twh_channel_idx');
		}

		return $schema;
	}
}
