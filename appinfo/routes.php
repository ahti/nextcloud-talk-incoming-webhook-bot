<?php

declare(strict_types=1);

return [
	'routes' => [
		['name' => 'webhook#handleWebhook', 'url' => '/api/v1/webhook/{hookId}', 'verb' => 'POST', 'requirements' => ['hookId' => '\d+']],
	],
];
