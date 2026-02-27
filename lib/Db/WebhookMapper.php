<?php

declare(strict_types=1);

namespace OCA\TalkWebhooks\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\Types;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Webhook>
 */
final class WebhookMapper extends QBMapper {
	/** @psalm-suppress PossiblyUnusedMethod Called by DI */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'talk_webhooks', Webhook::class);
	}

	/**
	 * @return list<Webhook>
	 * @throws Exception
	 */
	public function findByChannelToken(string $channelToken): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('channel_token', $qb->createNamedParameter($channelToken))
			);
		return $this->findEntities($qb);
	}

	/**
	 * @throws Exception
	 */
	public function findById(int $id): ?Webhook {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($id, Types::INTEGER))
			);
		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException) {
			return null;
		} catch (MultipleObjectsReturnedException) {
			return null;
		}
	}
}
