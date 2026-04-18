<?php

declare(strict_types=1);

return [
	'routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'adminSettings#save', 'url' => '/admin/address-books', 'verb' => 'POST'],
		['name' => 'adminSettings#syncDocumentReferences', 'url' => '/admin/document-references/sync', 'verb' => 'POST'],
		['name' => 'page#fetchContact', 'url' => '/contact/fetch', 'verb' => 'GET'],
		['name' => 'page#moveGroup', 'url' => '/group/move', 'verb' => 'POST'],
		['name' => 'page#downloadGroupVCard', 'url' => '/group/vcard', 'verb' => 'GET'],
		['name' => 'page#moveContact', 'url' => '/contact/move', 'verb' => 'POST'],
		['name' => 'page#downloadContactVCard', 'url' => '/contact/vcard', 'verb' => 'GET'],
		['name' => 'contactMeta#show', 'url' => '/contact-meta', 'verb' => 'GET'],
		['name' => 'contactMeta#save', 'url' => '/contact-meta', 'verb' => 'POST'],
		['name' => 'page#updateNote', 'url' => '/note', 'verb' => 'POST'],
		['name' => 'page#updateContact', 'url' => '/contact', 'verb' => 'POST'],
	],
];
