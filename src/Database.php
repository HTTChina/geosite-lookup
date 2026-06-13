<?php

declare(strict_types=1);

namespace GeoSitePhp;

final class Database
{
    /**
     * @return array<string, mixed>
     */
    public static function load(string $path): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException("Database file not found: {$path}");
        }

        $json = file_get_contents($path);
        if ($json === false) {
            throw new \RuntimeException("Unable to read database file: {$path}");
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new \RuntimeException("Invalid JSON database: {$path}");
        }

        return $data;
    }
}
