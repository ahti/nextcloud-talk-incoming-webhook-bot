<?php

declare(strict_types=1);

namespace OCA\TalkWebhooks\Service;

use OCA\Talk\Chat\ChatManager;
use OCA\Talk\Model\Attendee;
use OCA\Talk\Manager;
use OCA\TalkWebhooks\Db\Webhook;
use OCA\TalkWebhooks\Db\WebhookMapper;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IURLGenerator;
use OCP\Security\ISecureRandom;
use OCP\Server;
use Psr\Log\LoggerInterface;

final class WebhookService {
	private const BOT_URL = 'nextcloudapp://talk_webhooks';
	private const DEFAULT_SECRET_LENGTH = 32;
	private const MAX_NAME_LENGTH = 128;

	/** @psalm-suppress PossiblyUnusedMethod Called by DI */
	public function __construct(
		private WebhookMapper $mapper,
		private ISecureRandom $secureRandom,
		private IURLGenerator $urlGenerator,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @return array{webhook: Webhook, secret: string|null}
	 */
	public function create(string $channelToken, string $name = '', ?string $secretHash = null): array {
		if (strlen($name) > self::MAX_NAME_LENGTH) {
			throw new \InvalidArgumentException('Webhook name exceeds maximum length of ' . self::MAX_NAME_LENGTH . ' characters');
		}

		$webhook = new Webhook();
		$webhook->setChannelToken($channelToken);
		$webhook->setName($name);

		if ($secretHash !== null && $secretHash !== '') {
			$webhook->setSecretHash($secretHash);
			$plainSecret = null;
		} else {
			$plainSecret = $this->secureRandom->generate(self::DEFAULT_SECRET_LENGTH, ISecureRandom::CHAR_ALPHANUMERIC);
			$webhook->setSecretHash($this->hashSecret($plainSecret));
		}

		$webhook->setCreatedAt(time());

		$this->mapper->insert($webhook);

		$this->logger->info('Webhook created', [
			'webhookId' => $webhook->getId(),
			'channelToken' => $channelToken,
			'name' => $name,
		]);

		return [
			'webhook' => $webhook,
			'secret' => $plainSecret,
		];
	}

	public function hashSecret(string $secret): string {
		return password_hash($secret, PASSWORD_DEFAULT);
	}

	public function verifySecret(string $secret, string $hash): bool {
		return password_verify($secret, $hash);
	}

	/**
	 * @return list<Webhook>
	 */
	public function listByChannel(string $channelToken): array {
		return $this->mapper->findByChannelToken($channelToken);
	}

	public function delete(int $webhookId, string $channelToken): bool {
		$webhook = $this->mapper->findById($webhookId);
		if ($webhook === null  || $webhook->getChannelToken() !== $channelToken) {
			return false;
		}

		$this->mapper->delete($webhook);

		$this->logger->info('Webhook deleted', [
			'webhookId' => $webhookId,
			'channelToken' => $channelToken,
		]);
        
        return true;
	}

	public function findByIdAndSecret(int $hookId, string $secret): ?Webhook {
		$webhook = $this->mapper->findById($hookId);
		if ($webhook === null) {
			return null;
		}

		if (!$this->verifySecret($secret, $webhook->getSecretHash())) {
			return null;
		}

		return $webhook;
	}

	public function getWebhookUrl(Webhook $webhook): string {
		return $this->urlGenerator->getAbsoluteURL(
			'/apps/talk_webhooks/api/v1/webhook/' . $webhook->getId()
		);
	}

	/** @psalm-suppress UndefinedClass,MixedAssignment,MixedMethodCall,MixedArgument,MixedOperand */
	public function sendMessage(string $channelToken, string $message): DataResponse {
		try {
			$roomManager = Server::get(Manager::class);
			$room = $roomManager->getRoomByToken($channelToken);

			$chatManager = Server::get(ChatManager::class);

			$creationDateTime = new \DateTime('now', new \DateTimeZone('UTC'));

			$chatManager->sendMessage(
				$room,
				null,
				Attendee::ACTOR_BOTS,
				Attendee::ACTOR_BOT_PREFIX . sha1(self::BOT_URL),
				$message,
				$creationDateTime,
				null,
				'',
				false,
				false
			);

			return new DataResponse(['success' => true]);
		} catch (\Exception $e) {
			$this->logger->error('Failed to send message to Talk', [
				'exception' => $e,
				'channelToken' => $channelToken,
			]);
			return new DataResponse(
				['error' => 'Failed to send message. Check Nextcloud log for more.'],
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}
	}
}
