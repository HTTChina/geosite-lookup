<?php

declare(strict_types=1);

namespace GeoSitePhp;

final class DomainMatcher
{
    const TYPE_PLAIN = 0;
    const TYPE_REGEX = 1;
    const TYPE_DOMAIN = 2;
    const TYPE_FULL = 3;

    /**
     * @param array<string, mixed> $database
     * @return array<int, array<string, mixed>>
     */
    public function match(string $domain, array $database): array
    {
        $domain = Input::toDomain($domain);
        $matches = [];

        foreach (($database['sets'] ?? []) as $set) {
            if (!is_array($set)) {
                continue;
            }

            $label = (string) ($set['label'] ?? '');
            $rules = is_array($set['rules'] ?? null) ? $set['rules'] : [];
            $matchedRules = $this->matchedRules($domain, $rules);

            if ($label !== '' && $matchedRules !== []) {
                $matches[] = [
                    'label' => $label,
                    'rules' => $matchedRules,
                ];
            }
        }

        return $matches;
    }

    /**
     * @param array<int, array{label: string, domains: array<int, array{type: int, value: string}>}> $entries
     * @return array<int, array<string, mixed>>
     */
    public function matchDat(string $domain, array $entries): array
    {
        $domain = Input::toDomain($domain);
        $matches = [];

        foreach ($entries as $entry) {
            $matchedRules = [];

            foreach ($entry['domains'] as $rule) {
                if ($this->matchesDatRule($domain, $rule['type'], $rule['value'])) {
                    $matchedRules[] = [
                        'type' => $this->datTypeName($rule['type']),
                        'value' => $rule['value'],
                    ];
                }
            }

            if ($matchedRules !== []) {
                $matches[] = [
                    'label' => $entry['label'],
                    'rules' => $matchedRules,
                ];
            }
        }

        return $matches;
    }

    public function matchesDatRule(string $domain, int $type, string $rule): bool
    {
        switch ($type) {
            case self::TYPE_PLAIN:
                return $rule !== '' && strpos($domain, $rule) !== false;
            case self::TYPE_REGEX:
                return $this->matchesRegex($domain, $rule);
            case self::TYPE_DOMAIN:
                return $domain === $rule || $this->endsWith($domain, '.' . $rule);
            case self::TYPE_FULL:
                return $domain === $rule;
            default:
                return false;
        }
    }

    private function matchesRegex(string $domain, string $rule): bool
    {
        if ($rule === '') {
            return false;
        }

        set_error_handler(static function () {
            return true;
        });
        try {
            return preg_match('/' . str_replace('/', '\\/', $rule) . '/i', $domain) === 1;
        } finally {
            restore_error_handler();
        }
    }

    public function datTypeName(int $type): string
    {
        switch ($type) {
            case self::TYPE_PLAIN:
                return 'plain';
            case self::TYPE_REGEX:
                return 'regex';
            case self::TYPE_DOMAIN:
                return 'domain';
            case self::TYPE_FULL:
                return 'full';
            default:
                return 'unknown';
        }
    }

    /**
     * @param array<string, mixed> $rules
     * @return array<int, array{type: string, value: string}>
     */
    private function matchedRules(string $domain, array $rules): array
    {
        $matched = [];

        foreach ($this->strings($rules['domain'] ?? []) as $rule) {
            if ($domain === strtolower($rule)) {
                $matched[] = ['type' => 'domain', 'value' => $rule];
            }
        }

        foreach ($this->strings($rules['suffix'] ?? []) as $rule) {
            $rule = strtolower(ltrim($rule, '.'));
            if ($domain === $rule || $this->endsWith($domain, '.' . $rule)) {
                $matched[] = ['type' => 'suffix', 'value' => $rule];
            }
        }

        foreach ($this->strings($rules['keyword'] ?? []) as $rule) {
            $rule = strtolower($rule);
            if ($rule !== '' && strpos($domain, $rule) !== false) {
                $matched[] = ['type' => 'keyword', 'value' => $rule];
            }
        }

        return $matched;
    }

    /**
     * @param mixed $values
     * @return string[]
     */
    private function strings($values): array
    {
        if (!is_array($values)) {
            return [];
        }

        return array_values(array_filter($values, static function ($value): bool {
            return is_string($value) && $value !== '';
        }));
    }

    private function endsWith(string $value, string $suffix): bool
    {
        if ($suffix === '') {
            return true;
        }

        return substr($value, -strlen($suffix)) === $suffix;
    }
}
