<?php

declare(strict_types=1);

namespace GeoSitePhp;

final class Input
{
    public static function normalize(string $value): string
    {
        return trim($value);
    }

    public static function isIp(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    public static function toDomain(string $value): string
    {
        $value = trim($value);

        if (str_contains($value, '://')) {
            $host = parse_url($value, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                $value = $host;
            }
        }

        $value = preg_replace('/^www\./i', '', $value) ?? $value;
        $value = rtrim($value, '.');
        $value = strtolower($value);

        if (function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($value, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if (is_string($ascii) && $ascii !== '') {
                $value = strtolower($ascii);
            }
        }

        return $value;
    }
}
