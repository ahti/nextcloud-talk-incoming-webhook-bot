<?php

declare(strict_types=1);

namespace OCA\TalkWebhooks\Controller;

use OCA\TalkWebhooks\Service\WebhookService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

/**
 * @psalm-suppress UnusedClass Loaded via routes.php
 */
final class WebhookController extends Controller {
	private const MAX_MESSAGE_LENGTH = 30000;

	public function __construct(
		string $appName,
		IRequest $request,
		private WebhookService $webhookService,
	) {
		parent::__construct($appName, $request);
	}

	#[PublicPage]
	#[NoCSRFRequired]
	#[BruteForceProtection(action: 'talk_webhooks')]
	public function handleWebhook(int $hookId): DataResponse {
		$secret = $this->request->getHeader('X-Webhook-Secret');
		if ($secret === '') {
			return new DataResponse(
				['error' => 'Missing X-Webhook-Secret header'],
				Http::STATUS_UNAUTHORIZED
			);
		}

		$webhook = $this->webhookService->findByIdAndSecret($hookId, $secret);
		if ($webhook === null) {
			return new DataResponse(
				['error' => 'Invalid webhook or secret'],
				Http::STATUS_UNAUTHORIZED
			);
		}

		$message = (string)$this->request->getParam('message', '');

		if ($message === '') {
			return new DataResponse(
				['error' => 'Missing message in request body'],
				Http::STATUS_BAD_REQUEST
			);
		}

		if (strlen($message) > self::MAX_MESSAGE_LENGTH) {
			return new DataResponse(
				['error' => 'Message exceeds maximum length of ' . self::MAX_MESSAGE_LENGTH . ' characters'],
				Http::STATUS_BAD_REQUEST
			);
		}

		return $this->webhookService->sendMessage(
			$webhook->getChannelToken(),
			$message
		);
	}
}
