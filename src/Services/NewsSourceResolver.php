<?php

namespace JonesRussell\NorthCloud\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsSourceResolver
{
    /**
     * Resolve a NewsSource from an article URL.
     */
    public function resolve(string $url): Model
    {
        $domain = $this->extractDomain($url);
        $slug = Str::slug(str_replace('.', '-', $domain));
        $name = Str::title(str_replace('-', '.', $slug));
        $baseUrl = $this->extractBaseUrl($url);

        $model = config('northcloud.models.news_source');

        return $model::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'url' => $baseUrl,
                'is_active' => true,
            ]
        );
    }

    /**
     * Resolve a NewsSource from article data array using URL fallback chain.
     */
    public function resolveFromData(array $data): Model
    {
        $url = $data['canonical_url']
            ?? $data['og_url']
            ?? $data['source']
            ?? null;

        if ($url) {
            return $this->resolve($url);
        }

        $model = config('northcloud.models.news_source');

        return $model::firstOrCreate(
            ['slug' => 'unknown'],
            [
                'name' => 'Unknown Source',
                'url' => 'https://unknown',
                'is_active' => true,
            ]
        );
    }

    protected function extractDomain(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?? 'unknown';

        return preg_replace('/^www\./', '', $host);
    }

    protected function extractBaseUrl(string $url): string
    {
        $parsed = parse_url($url);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? 'unknown';

        return "{$scheme}://{$host}";
    }
}
