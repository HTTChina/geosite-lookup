<?php

declare(strict_types=1);

use GeoSitePhp\LookupService;

require __DIR__ . '/../src/bootstrap.php';

$service = new LookupService(__DIR__ . '/../data');

$cases = [
    ['google.com', 'domain', 'geosite:google'],
    ['https://chat.openai.com/', 'domain', 'geosite:openai'],
    ['www.baidu.com', 'domain', 'geosite:cn'],
    ['8.8.8.8', 'ip', 'geoip:google'],
    ['1.1.1.1', 'ip', 'geoip:cloudflare'],
    ['192.168.1.1', 'ip', 'geoip:private'],
    ['geosite:hetzner', 'geosite', 'geosite:hetzner'],
];

$failures = [];

foreach ($cases as $case) {
    list($input, $type, $expectedLabel) = $case;
    $result = $service->lookup($input);
    $labels = array_map(static function (array $match): string {
        return (string) $match['label'];
    }, $result['matches']);

    if ($result['type'] !== $type || !in_array($expectedLabel, $labels, true)) {
        $failures[] = [
            'input' => $input,
            'expected_type' => $type,
            'actual_type' => $result['type'],
            'expected_label' => $expectedLabel,
            'actual_labels' => $labels,
        ];
    }
}

if ($failures !== []) {
    echo json_encode($failures, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(1);
}

echo "All tests passed.\n";
