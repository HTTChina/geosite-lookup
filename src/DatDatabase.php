<?php

declare(strict_types=1);

namespace GeoSitePhp;

final class DatDatabase
{
    /**
     * @return array<string, mixed>|null
     */
    public static function findGeoSite(string $path, string $code)
    {
        $reader = self::readerFor($path);
        $target = strtolower($code);

        while (($tag = $reader->readTag()) !== null) {
            if ($tag['field'] === 1 && $tag['wire'] === 2) {
                $match = self::parseGeoSiteEntry($reader->readLengthDelimited(), $target);
                if ($match !== null) {
                    return $match;
                }
                continue;
            }

            $reader->skip($tag['wire']);
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function lookupGeoSite(string $path, string $domain): array
    {
        $reader = self::readerFor($path);
        $matcher = new DomainMatcher();
        $matches = [];

        while (($tag = $reader->readTag()) !== null) {
            if ($tag['field'] === 1 && $tag['wire'] === 2) {
                $match = self::matchGeoSite($reader->readLengthDelimited(), $domain, $matcher);
                if ($match !== null) {
                    $matches[] = $match;
                }
                continue;
            }

            $reader->skip($tag['wire']);
        }

        return $matches;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function lookupGeoIp(string $path, string $ip): array
    {
        $ipBytes = inet_pton($ip);
        if ($ipBytes === false) {
            return [];
        }

        $reader = self::readerFor($path);
        $matcher = new IpMatcher();
        $matches = [];

        while (($tag = $reader->readTag()) !== null) {
            if ($tag['field'] === 1 && $tag['wire'] === 2) {
                $match = self::matchGeoIp($reader->readLengthDelimited(), $ipBytes, $matcher);
                if ($match !== null) {
                    $matches[] = $match;
                }
                continue;
            }

            $reader->skip($tag['wire']);
        }

        return $matches;
    }

    /**
     * @return array<int, array{label: string, domains: array<int, array{type: int, value: string}>}>
     */
    public static function loadGeoSite(string $path): array
    {
        $reader = self::readerFor($path);
        $entries = [];

        while (($tag = $reader->readTag()) !== null) {
            if ($tag['field'] === 1 && $tag['wire'] === 2) {
                $entries[] = self::parseGeoSite($reader->readLengthDelimited());
                continue;
            }

            $reader->skip($tag['wire']);
        }

        return $entries;
    }

    /**
     * @return array<int, array{label: string, cidr: array<int, array{ip: string, prefix: int}>}>
     */
    public static function loadGeoIp(string $path): array
    {
        $reader = self::readerFor($path);
        $entries = [];

        while (($tag = $reader->readTag()) !== null) {
            if ($tag['field'] === 1 && $tag['wire'] === 2) {
                $entries[] = self::parseGeoIp($reader->readLengthDelimited());
                continue;
            }

            $reader->skip($tag['wire']);
        }

        return $entries;
    }

    private static function readerFor(string $path): ProtoReader
    {
        if (!is_file($path)) {
            throw new \RuntimeException("Dat file not found: {$path}");
        }

        $data = file_get_contents($path);
        if ($data === false) {
            throw new \RuntimeException("Unable to read dat file: {$path}");
        }

        return new ProtoReader($data);
    }

    /**
     * @return array{label: string, domains: array<int, array{type: int, value: string}>}
     */
    private static function parseGeoSite(string $data): array
    {
        $reader = new ProtoReader($data);
        $countryCode = '';
        $domains = [];

        while (($tag = $reader->readTag()) !== null) {
            if ($tag['field'] === 1 && $tag['wire'] === 2) {
                $countryCode = strtolower($reader->readLengthDelimited());
                continue;
            }

            if ($tag['field'] === 2 && $tag['wire'] === 2) {
                $domain = self::parseDomain($reader->readLengthDelimited());
                if ($domain['value'] !== '') {
                    $domains[] = $domain;
                }
                continue;
            }

            $reader->skip($tag['wire']);
        }

        return [
            'label' => 'geosite:' . $countryCode,
            'domains' => $domains,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function matchGeoSite(string $data, string $domain, DomainMatcher $matcher)
    {
        $reader = new ProtoReader($data);
        $label = '';
        $matchedRules = [];

        while (($tag = $reader->readTag()) !== null) {
            if ($tag['field'] === 1 && $tag['wire'] === 2) {
                $label = 'geosite:' . strtolower($reader->readLengthDelimited());
                continue;
            }

            if ($tag['field'] === 2 && $tag['wire'] === 2) {
                $rule = self::parseDomain($reader->readLengthDelimited());
                if ($matcher->matchesDatRule($domain, $rule['type'], $rule['value'])) {
                    $matchedRules[] = [
                        'type' => $matcher->datTypeName($rule['type']),
                        'value' => $rule['value'],
                    ];
                }
                continue;
            }

            $reader->skip($tag['wire']);
        }

        if ($label === '' || $matchedRules === []) {
            return null;
        }

        return [
            'label' => $label,
            'rules' => $matchedRules,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function parseGeoSiteEntry(string $data, string $target)
    {
        $reader = new ProtoReader($data);
        $countryCode = '';
        $rules = [];
        $matcher = new DomainMatcher();

        while (($tag = $reader->readTag()) !== null) {
            if ($tag['field'] === 1 && $tag['wire'] === 2) {
                $countryCode = strtolower($reader->readLengthDelimited());
                continue;
            }

            if ($tag['field'] === 2 && $tag['wire'] === 2) {
                $rule = self::parseDomain($reader->readLengthDelimited());
                if ($rule['value'] !== '') {
                    $rules[] = [
                        'type' => $matcher->datTypeName($rule['type']),
                        'value' => $rule['value'],
                    ];
                }
                continue;
            }

            $reader->skip($tag['wire']);
        }

        if ($countryCode !== $target) {
            return null;
        }

        return [
            'label' => 'geosite:' . $countryCode,
            'rules' => $rules,
        ];
    }

    /**
     * @return array{type: int, value: string}
     */
    private static function parseDomain(string $data): array
    {
        $reader = new ProtoReader($data);
        $type = 0;
        $value = '';

        while (($tag = $reader->readTag()) !== null) {
            if ($tag['field'] === 1 && $tag['wire'] === 0) {
                $type = $reader->readVarint();
                continue;
            }

            if ($tag['field'] === 2 && $tag['wire'] === 2) {
                $value = strtolower($reader->readLengthDelimited());
                continue;
            }

            $reader->skip($tag['wire']);
        }

        return [
            'type' => $type,
            'value' => $value,
        ];
    }

    /**
     * @return array{label: string, cidr: array<int, array{ip: string, prefix: int}>}
     */
    private static function parseGeoIp(string $data): array
    {
        $reader = new ProtoReader($data);
        $countryCode = '';
        $cidrs = [];

        while (($tag = $reader->readTag()) !== null) {
            if ($tag['field'] === 1 && $tag['wire'] === 2) {
                $countryCode = strtolower($reader->readLengthDelimited());
                continue;
            }

            if ($tag['field'] === 2 && $tag['wire'] === 2) {
                $cidr = self::parseCidr($reader->readLengthDelimited());
                if ($cidr['ip'] !== '') {
                    $cidrs[] = $cidr;
                }
                continue;
            }

            $reader->skip($tag['wire']);
        }

        return [
            'label' => 'geoip:' . $countryCode,
            'cidr' => $cidrs,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function matchGeoIp(string $data, string $ipBytes, IpMatcher $matcher)
    {
        $reader = new ProtoReader($data);
        $label = '';
        $matchedCidrs = [];

        while (($tag = $reader->readTag()) !== null) {
            if ($tag['field'] === 1 && $tag['wire'] === 2) {
                $label = 'geoip:' . strtolower($reader->readLengthDelimited());
                continue;
            }

            if ($tag['field'] === 2 && $tag['wire'] === 2) {
                $cidr = self::parseCidr($reader->readLengthDelimited());
                if ($matcher->bytesInCidr($ipBytes, $cidr['ip'], $cidr['prefix'])) {
                    $network = inet_ntop($cidr['ip']);
                    if ($network !== false) {
                        $matchedCidrs[] = $network . '/' . $cidr['prefix'];
                    }
                }
                continue;
            }

            $reader->skip($tag['wire']);
        }

        if ($label === '' || $matchedCidrs === []) {
            return null;
        }

        return [
            'label' => $label,
            'cidr' => $matchedCidrs,
        ];
    }

    /**
     * @return array{ip: string, prefix: int}
     */
    private static function parseCidr(string $data): array
    {
        $reader = new ProtoReader($data);
        $ip = '';
        $prefix = 0;

        while (($tag = $reader->readTag()) !== null) {
            if ($tag['field'] === 1 && $tag['wire'] === 2) {
                $ip = $reader->readLengthDelimited();
                continue;
            }

            if ($tag['field'] === 2 && $tag['wire'] === 0) {
                $prefix = $reader->readVarint();
                continue;
            }

            $reader->skip($tag['wire']);
        }

        return [
            'ip' => $ip,
            'prefix' => $prefix,
        ];
    }
}
