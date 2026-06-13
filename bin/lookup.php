#!/usr/bin/env php
<?php

declare(strict_types=1);

use GeoSitePhp\LookupService;

require __DIR__ . '/../src/bootstrap.php';

$input = $argv[1] ?? '';
if ($input === '') {
    fwrite(STDERR, "Usage: php bin/lookup.php <domain|url|ip>\n");
    exit(1);
}

$service = new LookupService(__DIR__ . '/../data');
$result = $service->lookup($input);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
