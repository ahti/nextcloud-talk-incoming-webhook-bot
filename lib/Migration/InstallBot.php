<?php

declare(strict_types=1);

namespace OCA\TalkWebhooks\Migration;

use OCA\TalkWebhooks\AppInfo\Application;
use OCA\Talk\Events\BotInstallEvent;
use OCA\Talk\Model\Bot;
use OCP\AppFramework\Services\IAppConfig;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Security\ISecureRandom;

/**
 * @psalm-suppress UnusedClass Loaded via repair-steps in info.xml
 */
final class InstallBot implements IRepairStep {
	public function __construct(
		protected IEventDispatcher $dispatcher,
		protected ISecureRandom $secureRandom,
		protected IAppConfig $appConfig,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Install as Talk bot';
	}

	/** @psalm-suppress UndefinedClass,MixedAssignment,MixedArgument */
	#[\Override]
	public function run(IOutput $output): void {
		// @psalm-suppress UndefinedClass BotInstallEvent/Bot not in vendor
		if (!class_exists(BotInstallEvent::class)) {
			$output->warning('Talk not found, not installing bots');
			return;
		}

		$secret = $this->appConfig->getAppValueString('bot_secret');
		if ($secret === '') {
			$secret = $this->secureRandom->generate(128);
			$this->appConfig->setAppValueString('bot_secret', $secret, true);
		}

		$event = new BotInstallEvent(
			'Incoming Webhooks',
			$secret,
			'nextcloudapp://' . Application::APP_ID,
			'Manage incoming webhooks for this channel. Use /webhook create, /webhook list, /webhook delete',
			Bot::FEATURE_EVENT
		);
        
		$this->dispatcher->dispatchTyped($event);
	}
}
