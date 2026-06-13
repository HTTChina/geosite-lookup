<?php

declare(strict_types=1);

namespace GeoSitePhp;

final class LookupService
{
    private $geosite = [];
    private $geoip = [];
    private $geositeDat = null;
    private $geoipDat = null;
    private $listDir;
    private $metadata;
    private $usesDat;

    public function __construct(string $dataDir)
    {
        $geositeDat = $dataDir . '/source/geosite.dat';
        $geoipDat = $dataDir . '/source/geoip.dat';
        $this->listDir = $dataDir . '/source/lists';
        $this->metadata = ReleaseMetadata::load($dataDir . '/source/metadata.json');
        $this->usesDat = is_file($geositeDat) && is_file($geoipDat);

        if ($this->usesDat) {
            $this->geositeDat = $geositeDat;
            $this->geoipDat = $geoipDat;
            return;
        }

        $this->geosite = Database::load($dataDir . '/geosite.json');
        $this->geoip = Database::load($dataDir . '/geoip.json');
    }

    /**
     * @return array<string, mixed>
     */
    public function lookup(string $input): array
    {
        $input = Input::normalize($input);

        if ($input === '') {
            return [
                'input' => $input,
                'type' => 'empty',
                'normalized' => '',
                'source' => $this->usesDat ? 'dat' : 'json',
                'versions' => $this->versions(),
                'matches' => [],
                'list_matches' => [],
            ];
        }

        if (Input::isIp($input)) {
            $matcher = new IpMatcher();
            $matches = $this->usesDat
                ? DatDatabase::lookupGeoIp((string) $this->geoipDat, $input)
                : $matcher->match($input, $this->geoip);

            return [
                'input' => $input,
                'type' => 'ip',
                'normalized' => $input,
                'source' => $this->usesDat ? 'dat' : 'json',
                'versions' => $this->versions(),
                'matches' => $matches,
                'list_matches' => [],
            ];
        }

        $domain = Input::toDomain($input);
        $matcher = new DomainMatcher();
        $matches = $this->usesDat
            ? DatDatabase::lookupGeoSite((string) $this->geositeDat, $domain)
            : $matcher->match($domain, $this->geosite);
        $listMatches = (new TextListMatcher())->matchDomain($domain, $this->listDir);

        return [
            'input' => $input,
            'type' => 'domain',
            'normalized' => $domain,
            'source' => $this->usesDat ? 'dat' : 'json',
            'versions' => $this->versions(),
            'matches' => $matches,
            'list_matches' => $listMatches,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function versions(): array
    {
        return [
            'geosite' => $this->metadata['geosite'] ?? null,
            'geoip' => $this->metadata['geoip'] ?? null,
            'lists' => $this->metadata['lists'] ?? [],
        ];
    }
}
