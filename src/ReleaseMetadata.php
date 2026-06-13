<?php

declare(strict_types=1);

namespace GeoSitePhp;

final class ReleaseMetadata
{
    /**
     * @return array<string, mixed>
     */
    public static function load(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $json = file_get_contents($path);
        if ($json === false) {
            return [];
        }

        $data = json_decode($json, true);

        return is_array($data) ? $data : [];
    }
}
