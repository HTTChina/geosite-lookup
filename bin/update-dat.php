#!/usr/bin/env php
<?php

declare(strict_types=1);

$releases = [
    'geoip' => [
        'api' => 'https://api.github.com/repos/Loyalsoldier/geoip/releases/latest',
        'asset' => 'geoip.dat',
        'path' => __DIR__ . '/../data/source/geoip.dat',
    ],
    'geosite' => [
        'api' => 'https://api.github.com/repos/Loyalsoldier/v2ray-rules-dat/releases/latest',
        'asset' => 'geosite.dat',
        'path' => __DIR__ . '/../data/source/geosite.dat',
        'extra_assets' => [
            'apple-cn.txt',
            'china-list.txt',
            'direct-list.txt',
            'direct-tld-list.txt',
            'gfw.txt',
            'google-cn.txt',
        ],
    ],
];

$sourceDir = __DIR__ . '/../data/source';
if (!is_dir($sourceDir) && !mkdir($sourceDir, 0775, true) && !is_dir($sourceDir)) {
    fwrite(STDERR, "Unable to create directory: {$sourceDir}\n");
    exit(1);
}

$listDir = $sourceDir . '/lists';
if (!is_dir($listDir) && !mkdir($listDir, 0775, true) && !is_dir($listDir)) {
    fwrite(STDERR, "Unable to create directory: {$listDir}\n");
    exit(1);
}

$metadata = [
    'updated_at' => gmdate('c'),
];

foreach ($releases as $key => $releaseConfig) {
    $release = fetchJson($releaseConfig['api']);
    $asset = findAsset($release, $releaseConfig['asset']);
    $path = $releaseConfig['path'];
    $url = (string) $asset['browser_download_url'];

    downloadAsset($url, $path);

    $metadata[$key] = [
        'repository' => $key === 'geoip' ? 'Loyalsoldier/geoip' : 'Loyalsoldier/v2ray-rules-dat',
        'tag_name' => (string) ($release['tag_name'] ?? ''),
        'name' => (string) ($release['name'] ?? ''),
        'published_at' => (string) ($release['published_at'] ?? ''),
        'release_url' => (string) ($release['html_url'] ?? ''),
        'asset_name' => (string) ($asset['name'] ?? ''),
        'asset_size' => (int) ($asset['size'] ?? 0),
        'asset_digest' => (string) ($asset['digest'] ?? ''),
        'asset_url' => $url,
        'assets' => array_values(array_map(
            static function (array $item): string {
                return (string) ($item['name'] ?? '');
            },
            is_array($release['assets'] ?? null) ? $release['assets'] : []
        )),
    ];

    foreach (($releaseConfig['extra_assets'] ?? []) as $extraAssetName) {
        $extraAsset = findAsset($release, $extraAssetName);
        $extraPath = $listDir . '/' . $extraAssetName;
        $extraUrl = (string) $extraAsset['browser_download_url'];
        downloadAsset($extraUrl, $extraPath);

        $metadata['lists'][$extraAssetName] = [
            'repository' => 'Loyalsoldier/v2ray-rules-dat',
            'tag_name' => (string) ($release['tag_name'] ?? ''),
            'published_at' => (string) ($release['published_at'] ?? ''),
            'release_url' => (string) ($release['html_url'] ?? ''),
            'asset_name' => (string) ($extraAsset['name'] ?? ''),
            'asset_size' => (int) ($extraAsset['size'] ?? 0),
            'asset_digest' => (string) ($extraAsset['digest'] ?? ''),
            'asset_url' => $extraUrl,
            'path' => 'lists/' . $extraAssetName,
        ];
    }
}

file_put_contents(
    __DIR__ . '/../data/source/metadata.json',
    json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL
);

echo "Done.\n";

/**
 * @return array<string, mixed>
 */
function fetchJson(string $url): array
{
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: geositephp\r\nAccept: application/vnd.github+json\r\n",
        ],
    ]);

    $json = file_get_contents($url, false, $context);
    if ($json === false) {
        fwrite(STDERR, "Unable to fetch release metadata: {$url}\n");
        exit(1);
    }

    $data = json_decode($json, true);
    if (!is_array($data)) {
        fwrite(STDERR, "Invalid release metadata: {$url}\n");
        exit(1);
    }

    return $data;
}

/**
 * @param array<string, mixed> $release
 * @return array<string, mixed>
 */
function findAsset(array $release, string $name): array
{
    $assets = is_array($release['assets'] ?? null) ? $release['assets'] : [];

    foreach ($assets as $asset) {
        if (is_array($asset) && ($asset['name'] ?? null) === $name) {
            return $asset;
        }
    }

    fwrite(STDERR, "Asset not found: {$name}\n");
    exit(1);
}

function downloadAsset(string $url, string $path)
{
    echo "Downloading {$url}\n";

    $input = fopen($url, 'rb');
    if ($input === false) {
        fwrite(STDERR, "Unable to open URL: {$url}\n");
        exit(1);
    }

    $tmpPath = $path . '.tmp';
    $output = fopen($tmpPath, 'wb');
    if ($output === false) {
        fclose($input);
        fwrite(STDERR, "Unable to write file: {$tmpPath}\n");
        exit(1);
    }

    stream_copy_to_stream($input, $output);
    fclose($input);
    fclose($output);

    if (!rename($tmpPath, $path)) {
        fwrite(STDERR, "Unable to move {$tmpPath} to {$path}\n");
        exit(1);
    }

    echo "Saved {$path}\n";
}
