<?php

declare(strict_types=1);

return [
	'routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'page#fetchContact', 'url' => '/contact/fetch', 'verb' => 'GET'],
		['name' => 'page#updateNote', 'url' => '/note', 'verb' => 'POST'],
		['name' => 'page#updateContact', 'url' => '/contact', 'verb' => 'POST'],
	],
];
