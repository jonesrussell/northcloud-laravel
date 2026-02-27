<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Console\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class NorthCloudDoctor extends Command
{
    protected $signature = 'northcloud:doctor';

    protected $description = 'Validate NorthCloud configuration';

    public function handle(): int
    {
        $errors = [];

        if (config()->has('northcloud.redis.channel')) {
            $errors[] = '"northcloud.redis.channel" (singular) should be "northcloud.redis.channels" (array).';
        }

        if (config()->has('northcloud.processing.processor')) {
            $errors[] = '"northcloud.processing.processor" should be root-level "northcloud.processors" (array).';
        }

        $knownTopKeys = [
            'migrations', 'redis', 'quality', 'models', 'processors', 'processing',
            'content', 'tags', 'navigation', 'articleable', 'users', 'admin', 'mail', 'linking',
            'mcp',
        ];

        foreach (array_keys(config('northcloud', [])) as $key) {
            if (! in_array($key, $knownTopKeys, true)) {
                $errors[] = "Unknown top-level config key: \"{$key}\".";
            }
        }

        $knownNested = ['redis' => ['connection', 'channels'], 'content' => ['allowed_tags'], 'processing' => ['sync']];
        foreach ($knownNested as $section => $allowed) {
            foreach (array_keys(config("northcloud.{$section}", [])) as $nestedKey) {
                if (! in_array($nestedKey, $allowed, true)) {
                    $errors[] = "Unknown config key: \"{$section}.{$nestedKey}\".";
                }
            }
        }

        if ($errors === []) {
            info('All checks passed. NorthCloud configuration is valid.');

            return self::SUCCESS;
        }

        foreach ($errors as $err) {
            error($err);
        }

        return self::FAILURE;
    }
}
