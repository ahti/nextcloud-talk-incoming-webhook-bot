<?php

declare(strict_types=1);

namespace OCA\TalkWebhooks\Listener;

use OCA\TalkWebhooks\Service\WebhookService;
use OCA\Talk\Events\BotInvokeEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<Event>
 */
final class BotInvokeListener implements IEventListener {
	/** @psalm-suppress PossiblyUnusedMethod Called by DI */
	public function __construct(
		protected WebhookService $webhookService,
	) {
	}

	/** @psalm-suppress UndefinedClass,RedundantConditionGivenDocblockType */
	#[\Override]
	public function handle(Event $event): void {
		// Handle both OCP\EventDispatcher\Event and OCA\Talk\Events\BotInvokeEvent
		if (!($event instanceof BotInvokeEvent)) {
			return;
		}

		/** @var array{type: string, target: array{id: string}, object: array{content: string}, actor: array{talkParticipantType?: string}} $message */
		$message = $event->getMessage();
		if ($message['type'] !== 'Create') {
			return;
		}

		$channelToken = $message['target']['id'];
		
		/** @var array{message: string}|false $decoded */
		$decoded = json_decode($message['object']['content'], true);
		$text = is_array($decoded) ? ($decoded['message'] ?? '') : '';

		$parts = array_filter(preg_split('/\s+/', trim($text), 3) ?: []);
		$command = $parts[0] ?? '';
		$subcommand = $parts[1] ?? '';
		$subArgs = $parts[2] ?? '';

		if ($command !== '/webhook') {
			return;
		}
		
		// Check if user is moderator or owner (1=Owner, 2=Moderator, 6=Guest moderator)
		if (!in_array((int) ($message['actor']['talkParticipantType'] ?? 0), [1, 2, 6], true)) {
			$event->addAnswer('Only moderators and owners can manage webhooks.');
			return;
		}

		try {
			$response = match ($subcommand) {
				'create' => $this->handleCreate($channelToken, $subArgs),
				'list' => $this->handleList($channelToken),
				'delete' => $this->handleDelete($channelToken, $subArgs),
				default => $this->getHelpText(),
			};
		} catch (\Exception $e) {
			$response = 'An error occurred while processing your request. Please try again.';
		}

		$event->addAnswer($response);
	}

	private function handleCreate(string $channelToken, string $subArgs): string {
		$argsParts = preg_split('/\s+/', trim($subArgs), 2);
		$name = $argsParts[0] ?? null;
		$secretHash = $argsParts[1] ?? null;

		if ($name === null) {
			return 'Usage: /webhook create <name> [hash]';
		}

		if ($secretHash !== null && !preg_match('/^\$2[aby]\$\d{2}\$/', $secretHash)) {
			return 'Error: Invalid hash format. Provide a bcrypt hash (e.g. $2y$10$...) or leave empty to auto-generate.';
		}

		try {
			$result = $this->webhookService->create($channelToken, $name, $secretHash);
		} catch (\InvalidArgumentException $e) {
			return 'Error: ' . $e->getMessage();
		}
		
		$webhook = $result['webhook'];
		$generatedSecret = $result['secret'];
		$url = $this->webhookService->getWebhookUrl($webhook);
		$id = $webhook->getId();

		$response = <<<EOD
			Webhook created!
			
			| Name | {$name} |
			| ---- | ------- |
			| Id   | {$id}   |
			| URL  | {$url}  |
			
			EOD;

		if ($generatedSecret !== null) {
			$response .= <<<EOD
				| Secret | {$generatedSecret} |
				
				Copy the secret now - it won't be shown again.
				
				> ⚠️ **Warning: Anyone who can read this message can use the webhook!**
				> 
				> To secure your webhook, provide a custom bcrypt hash instead of auto-generating.
				> You can create one with htpasswd:
				> 
				> ```sh
				> htpasswd -nbBC 10 user "your-secret" | cut -d: -f2
				> ```
				
				EOD;
		}
		
		$exampleSecret = $generatedSecret ?? '<secret>';

		$response .= <<<EOD
			
			To send a message, POST to the URL with the secret in the `X-Webhook-Secret` header.
			
			Shell example: 
			```
			curl {$url} \\
				-H 'X-Webhook-Secret: {$exampleSecret}' \\
				--json '{"message":"Hello!"}'
			```
			EOD;

		return $response;
	}

	private function handleList(string $channelToken): string {
		$webhooks = $this->webhookService->listByChannel($channelToken);
		if (empty($webhooks)) {
			return 'No webhooks configured for this channel.';
		}

		$response = "Webhooks for this channel:\n";
		foreach ($webhooks as $webhook) {
			$id = $webhook->getId();
			$name = $webhook->getName() ?: '(unnamed)';
			$created = date('Y-m-d H:i', $webhook->getCreatedAt());
			$response .= "- {$id} - {$name} (created: {$created})\n";
		}

		return $response;
	}

	private function handleDelete(string $channelToken, string $hookId): string {
		$intId = filter_var($hookId, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
		if ($intId === null) {
			return 'Usage: /webhook delete <hook_id>';
		}

		$deleted = $this->webhookService->delete($intId, $channelToken);

		if (!$deleted) {
			return 'Webhook not found.';
		}

		return 'Webhook deleted.';
	}

	private function getHelpText(): string {
		return "Webhook commands:\n"
			. "- /webhook create <name> [secret_hash] - Create a new webhook\n"
			. "- /webhook list - List webhooks for this channel\n"
			. '- /webhook delete <hook_id> - Delete a webhook';
	}
}
