<?php

declare(strict_types=1);

$infoXmlPath = dirname(__DIR__) . '/appinfo/info.xml';
$contents = @file_get_contents($infoXmlPath);

if ($contents === false) {
	fwrite(STDERR, "Could not read {$infoXmlPath}\n");
	exit(1);
}

$navigationCount = 0;
$updatedCount = 0;

$updatedContents = preg_replace_callback(
	'/<navigation\b[^>]*>.*?<\/navigation>/s',
	static function (array $matches) use (&$navigationCount, &$updatedCount): string {
		$navigationCount++;
		$navigationBlock = $matches[0];

		if (preg_match('/<role>\s*[^<]+\s*<\/role>/', $navigationBlock) === 1) {
			return $navigationBlock;
		}

		$updatedCount++;

		return preg_replace(
			'/(\s*)<\/navigation>$/',
			"$1  <role>admin</role>$1</navigation>",
			$navigationBlock,
			1,
		) ?? $navigationBlock;
	},
	$contents,
);

if ($updatedContents === null) {
	fwrite(STDERR, "Failed to process navigation entries in {$infoXmlPath}\n");
	exit(1);
}

if ($navigationCount === 0) {
	fwrite(STDERR, "No <navigation> entries found in {$infoXmlPath}\n");
	exit(1);
}

if ($updatedCount === 0) {
	fwrite(STDOUT, "No changes needed. All navigation entries already define a role.\n");
	exit(0);
}

if (file_put_contents($infoXmlPath, $updatedContents) === false) {
	fwrite(STDERR, "Could not write {$infoXmlPath}\n");
	exit(1);
}

fwrite(STDOUT, "Added <role>admin</role> to {$updatedCount} navigation entr" . ($updatedCount === 1 ? "y" : "ies") . ".\n");
