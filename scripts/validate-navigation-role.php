<?php

declare(strict_types=1);

$infoXmlPath = dirname(__DIR__) . '/appinfo/info.xml';
$contents = @file_get_contents($infoXmlPath);

if ($contents === false) {
	fwrite(STDERR, "Could not read {$infoXmlPath}\n");
	exit(1);
}

$navigationCount = 0;
$missingRoleCount = 0;

$result = preg_match_all('/<navigation\b[^>]*>.*?<\/navigation>/s', $contents, $matches);
if ($result === false) {
	fwrite(STDERR, "Failed to scan navigation entries in {$infoXmlPath}\n");
	exit(1);
}

$navigationBlocks = $matches[0] ?? [];
$navigationCount = count($navigationBlocks);

if ($navigationCount === 0) {
	fwrite(STDERR, "No <navigation> entries found in {$infoXmlPath}\n");
	exit(1);
}

foreach ($navigationBlocks as $index => $navigationBlock) {
	if (preg_match('/<role>\s*[^<]+\s*<\/role>/', $navigationBlock) === 1) {
		continue;
	}

	$missingRoleCount++;
	fwrite(STDERR, 'Missing <role> in navigation entry #' . ($index + 1) . ".\n");
}

if ($missingRoleCount > 0) {
	fwrite(STDERR, "Validation failed: {$missingRoleCount} navigation entr" . ($missingRoleCount === 1 ? 'y is' : 'ies are') . " missing a <role>.\n");
	exit(1);
}

fwrite(STDOUT, "Validation passed: all {$navigationCount} navigation entries define a <role>.\n");
