<?php

declare(strict_types=1);

namespace OCA\TalkWebhooks\AppInfo;

use OCA\TalkWebhooks\Listener\BotInvokeListener;
use OCA\Talk\Events\BotInvokeEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

final class Application extends App implements IBootstrap {
	public const APP_ID = 'talk_webhooks';

	/** @psalm-suppress PossiblyUnusedMethod Called by DI */
	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	/** @psalm-suppress UndefinedClass,MixedArgument,InvalidArgument,MissingTemplateParam */
	#[\Override]
	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(BotInvokeEvent::class, BotInvokeListener::class);
	}

	#[\Override]
	public function boot(IBootContext $context): void {
	}
}
