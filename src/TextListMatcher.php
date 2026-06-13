<?php

declare(strict_types=1);

namespace GeoSitePhp;

final class TextListMatcher
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function matchDomain(string $domain, string $listDir): array
    {
        $domain = Input::toDomain($domain);
        if (!is_dir($listDir)) {
            return [];
        }

        $matches = [];
        $files = glob($listDir . '/*.txt');
        if ($files === false) {
            return [];
        }

        sort($files);

        foreach ($files as $file) {
            $matchedRules = $this->matchFile($domain, $file);
            if ($matchedRules !== []) {
                $matches[] = [
                    'list' => basename($file),
                    'rules' => $matchedRules,
                ];
            }
        }

        return $matches;
    }

    /**
     * @return array<int, array{type: string, value: string, line: int}>
     */
    private function matchFile(string $domain, string $file): array
    {
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            return [];
        }

        $matches = [];
        $lineNumber = 0;
        $isTldList = str_contains(basename($file), 'tld');

        while (($line = fgets($handle)) !== false) {
            $lineNumber++;
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$type, $value] = $this->parseLine($line, $isTldList);
            if ($this->matchesRule($domain, $type, $value)) {
                $matches[] = [
                    'type' => $type,
                    'value' => $value,
                    'line' => $lineNumber,
                ];
            }
        }

        fclose($handle);

        return $matches;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseLine(string $line, bool $isTldList): array
    {
        if (str_contains($line, ':')) {
            [$type, $value] = explode(':', $line, 2);
            $type = strtolower(trim($type));
            $value = strtolower(trim($value));

            if (in_array($type, ['full', 'domain', 'regexp', 'keyword'], true)) {
                return [$type, $value];
            }
        }

        return [$isTldList ? 'tld' : 'domain', strtolower($line)];
    }

    private function matchesRule(string $domain, string $type, string $value): bool
    {
        if ($value === '') {
            return false;
        }

        return match ($type) {
            'full' => $domain === $value,
            'domain' => $domain === $value || str_ends_with($domain, '.' . $value),
            'keyword' => str_contains($domain, $value),
            'regexp' => $this->matchesRegex($domain, $value),
            'tld' => $this->matchesTld($domain, $value),
            default => false,
        };
    }

    private function matchesRegex(string $domain, string $value): bool
    {
        set_error_handler(static fn (): bool => true);
        try {
            return preg_match('/' . str_replace('/', '\\/', $value) . '/i', $domain) === 1;
        } finally {
            restore_error_handler();
        }
    }

    private function matchesTld(string $domain, string $value): bool
    {
        $labels = explode('.', $domain);
        $tld = end($labels);

        return is_string($tld) && $tld === $value;
    }
}
