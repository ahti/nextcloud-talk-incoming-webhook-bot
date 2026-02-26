<?php

declare(strict_types=1);

namespace OCA\TalkWebhooks\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method int getId()
 * @method string getChannelToken()
 * @method void setChannelToken(string $channelToken)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getSecretHash()
 * @method void setSecretHash(string $secretHash)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-suppress PossiblyUnusedProperty
 */
final class Webhook extends Entity {
	protected string $channelToken = '';
	protected string $name = '';
	protected string $secretHash = '';
	protected int $createdAt = 0;

	public function __construct() {
		$this->addType('channelToken', 'string');
		$this->addType('name', 'string');
		$this->addType('secretHash', 'string');
		$this->addType('createdAt', 'integer');
	}
}
