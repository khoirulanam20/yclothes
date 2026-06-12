<?php

namespace App\Services;

use App\Support\WilayahCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WilayahService
{
    private const BASE_URL = 'https://wilayah.id/api';

    private const CACHE_TTL = 86400;

    /**
     * @return list<array{id: string, name: string}>
     */
    public function provinces(): array
    {
        return Cache::remember('wilayah.provinces', self::CACHE_TTL, function () {
            return $this->fetch(self::BASE_URL.'/provinces.json');
        });
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function regencies(string $provinceCode): array
    {
        $provinceCode = WilayahCode::normalize($provinceCode) ?? $provinceCode;

        return Cache::remember("wilayah.regencies.{$provinceCode}", self::CACHE_TTL, function () use ($provinceCode) {
            return $this->fetch(self::BASE_URL.'/regencies/'.urlencode($provinceCode).'.json');
        });
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function districts(string $regencyCode): array
    {
        $regencyCode = WilayahCode::normalize($regencyCode) ?? $regencyCode;

        return Cache::remember("wilayah.districts.{$regencyCode}", self::CACHE_TTL, function () use ($regencyCode) {
            return $this->fetch(self::BASE_URL.'/districts/'.urlencode($regencyCode).'.json');
        });
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function villages(string $districtCode): array
    {
        $districtCode = WilayahCode::normalize($districtCode) ?? $districtCode;

        return Cache::remember("wilayah.villages.{$districtCode}", self::CACHE_TTL, function () use ($districtCode) {
            return $this->fetch(self::BASE_URL.'/villages/'.urlencode($districtCode).'.json');
        });
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function fetch(string $url): array
    {
        $response = Http::timeout(10)->get($url);

        if (! $response->successful()) {
            return [];
        }

        $json = $response->json();

        if (! is_array($json)) {
            return [];
        }

        $rows = $json['data'] ?? $json;

        if (! is_array($rows)) {
            return [];
        }

        return array_map(fn (array $row) => [
            'id' => WilayahCode::normalize((string) ($row['code'] ?? $row['id'] ?? '')) ?? '',
            'name' => (string) ($row['name'] ?? ''),
        ], $rows);
    }
}
