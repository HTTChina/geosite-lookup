<?php

declare(strict_types=1);

namespace GeoSitePhp;

final class IpMatcher
{
    /**
     * @param array<string, mixed> $database
     * @return array<int, array<string, mixed>>
     */
    public function match(string $ip, array $database): array
    {
        if (!Input::isIp($ip)) {
            return [];
        }

        $matches = [];

        foreach (($database['sets'] ?? []) as $set) {
            if (!is_array($set)) {
                continue;
            }

            $label = (string) ($set['label'] ?? '');
            $cidrs = is_array($set['cidr'] ?? null) ? $set['cidr'] : [];
            $matchedCidrs = [];

            foreach ($cidrs as $cidr) {
                if (is_string($cidr) && $this->ipInCidr($ip, $cidr)) {
                    $matchedCidrs[] = $cidr;
                }
            }

            if ($label !== '' && $matchedCidrs !== []) {
                $matches[] = [
                    'label' => $label,
                    'cidr' => $matchedCidrs,
                ];
            }
        }

        return $matches;
    }

    /**
     * @param array<int, array{label: string, cidr: array<int, array{ip: string, prefix: int}>}> $entries
     * @return array<int, array<string, mixed>>
     */
    public function matchDat(string $ip, array $entries): array
    {
        if (!Input::isIp($ip)) {
            return [];
        }

        $ipBytes = inet_pton($ip);
        if ($ipBytes === false) {
            return [];
        }

        $matches = [];

        foreach ($entries as $entry) {
            $matchedCidrs = [];

            foreach ($entry['cidr'] as $cidr) {
                if ($this->bytesInCidr($ipBytes, $cidr['ip'], $cidr['prefix'])) {
                    $network = inet_ntop($cidr['ip']);
                    if ($network !== false) {
                        $matchedCidrs[] = $network . '/' . $cidr['prefix'];
                    }
                }
            }

            if ($matchedCidrs !== []) {
                $matches[] = [
                    'label' => $entry['label'],
                    'cidr' => $matchedCidrs,
                ];
            }
        }

        return $matches;
    }

    private function ipInCidr(string $ip, string $cidr): bool
    {
        [$network, $prefix] = array_pad(explode('/', $cidr, 2), 2, null);
        if ($network === null || $prefix === null || !ctype_digit($prefix)) {
            return false;
        }

        $ipBytes = inet_pton($ip);
        $networkBytes = inet_pton($network);
        if ($ipBytes === false || $networkBytes === false || strlen($ipBytes) !== strlen($networkBytes)) {
            return false;
        }

        $prefixLength = (int) $prefix;
        $maxBits = strlen($ipBytes) * 8;
        if ($prefixLength < 0 || $prefixLength > $maxBits) {
            return false;
        }

        $fullBytes = intdiv($prefixLength, 8);
        $remainingBits = $prefixLength % 8;

        if ($fullBytes > 0 && substr($ipBytes, 0, $fullBytes) !== substr($networkBytes, 0, $fullBytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xff << (8 - $remainingBits)) & 0xff;
        return (ord($ipBytes[$fullBytes]) & $mask) === (ord($networkBytes[$fullBytes]) & $mask);
    }

    public function bytesInCidr(string $ipBytes, string $networkBytes, int $prefixLength): bool
    {
        if (strlen($ipBytes) !== strlen($networkBytes)) {
            return false;
        }

        $maxBits = strlen($ipBytes) * 8;
        if ($prefixLength < 0 || $prefixLength > $maxBits) {
            return false;
        }

        $fullBytes = intdiv($prefixLength, 8);
        $remainingBits = $prefixLength % 8;

        if ($fullBytes > 0 && substr($ipBytes, 0, $fullBytes) !== substr($networkBytes, 0, $fullBytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xff << (8 - $remainingBits)) & 0xff;
        return (ord($ipBytes[$fullBytes]) & $mask) === (ord($networkBytes[$fullBytes]) & $mask);
    }
}
