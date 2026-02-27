<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Support;

use Illuminate\Support\Facades\Log;

class ConfigValidator
{
    /** @var list<string> */
    private const KNOWN_TOP_KEYS = [
        'migrations', 'redis', 'quality', 'models', 'processors', 'processing',
        'content', 'tags', 'navigation', 'articleable', 'users', 'admin', 'mail', 'linking',
        'mcp',
    ];

    /** @var array<string, list<string>> */
    private const KNOWN_NESTED_KEYS = [
        'redis' => ['connection', 'channels'],
        'content' => ['allowed_tags'],
        'processing' => ['sync'],
    ];

    /** @var array<string, list<string>> Deprecated keys already reported by checkDeprecatedKeys */
    private const DEPRECATED_NESTED_KEYS = [
        'redis' => ['channel'],
        'processing' => ['processor'],
    ];

    public function validate(): void
    {
        $this->checkDeprecatedKeys();
        $this->checkUnknownKeys();
    }

    private function checkDeprecatedKeys(): void
    {
        if (config()->has('northcloud.redis.channel')) {
            Log::warning(
                'NorthCloud config: "northcloud.redis.channel" (singular) is deprecated. '
                .'Use "northcloud.redis.channels" (array) instead.'
            );
        }

        if (config()->has('northcloud.processing.processor')) {
            Log::warning(
                'NorthCloud config: "northcloud.processing.processor" (singular) is deprecated. '
                .'Use the root-level "northcloud.processors" (array) instead.'
            );
        }
    }

    private function checkUnknownKeys(): void
    {
        $config = config('northcloud', []);

        foreach ($config as $key => $value) {
            if (! in_array($key, self::KNOWN_TOP_KEYS, true)) {
                Log::warning("NorthCloud config: unknown top-level key \"{$key}\".");
            }
        }

        foreach (self::KNOWN_NESTED_KEYS as $section => $allowedKeys) {
            $sectionConfig = config("northcloud.{$section}", []);
            if (! is_array($sectionConfig)) {
                continue;
            }
            $deprecated = self::DEPRECATED_NESTED_KEYS[$section] ?? [];
            foreach (array_keys($sectionConfig) as $nestedKey) {
                if (in_array($nestedKey, $deprecated, true)) {
                    continue;
                }
                if (! in_array($nestedKey, $allowedKeys, true)) {
                    Log::warning("NorthCloud config: unknown key \"{$section}.{$nestedKey}\".");
                }
            }
        }
    }
}
