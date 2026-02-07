<?php

namespace JonesRussell\NorthCloud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ArticlesTestPublish extends Command
{
    protected $signature = 'articles:test-publish
        {--channel= : Target Redis channel (defaults to first configured channel)}
        {--quality=75 : Quality score for the test article}
        {--dry-run : Show the payload without publishing}';

    protected $description = 'Publish a test article to verify end-to-end pipeline';

    public function handle(): int
    {
        $channel = $this->option('channel')
            ?? config('northcloud.redis.channels.0', 'articles:default');
        $quality = (int) $this->option('quality');

        $payload = $this->buildTestPayload($channel, $quality);
        $json = json_encode($payload, JSON_PRETTY_PRINT);

        if ($this->option('dry-run')) {
            $this->info('Dry run — would publish to: ' . $channel);
            $this->newLine();
            $this->line($json);

            return self::SUCCESS;
        }

        $connection = config('northcloud.redis.connection', 'northcloud');
        $redisConfig = config("database.redis.{$connection}");

        if (! $redisConfig) {
            $this->error("Redis connection [{$connection}] not configured.");

            return self::FAILURE;
        }

        try {
            $client = new \Redis;
            $host = $redisConfig['host'] ?? '127.0.0.1';
            $port = (int) ($redisConfig['port'] ?? 6379);
            $password = $redisConfig['password'] ?? null;

            $client->connect($host, $port);
            if ($password) {
                $client->auth($password);
            }

            $subscribers = $client->publish($channel, json_encode($payload));
            $client->close();

            $this->info("Published test article to [{$channel}] ({$subscribers} subscriber(s) received)");
            $this->line("External ID: {$payload['id']}");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to publish: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function buildTestPayload(string $channel, int $quality): array
    {
        $id = 'test-' . Str::uuid();

        return [
            'id' => $id,
            'title' => 'Test Article: ' . fake()->sentence(6),
            'canonical_url' => 'https://test.northcloud.example/' . Str::slug(fake()->sentence(3)),
            'source' => 'https://test.northcloud.example',
            'published_date' => now()->toIso8601String(),
            'publisher' => [
                'route_id' => 'test-route',
                'published_at' => now()->toIso8601String(),
                'channel' => $channel,
            ],
            'intro' => fake()->paragraph(),
            'body' => '<p>' . fake()->paragraphs(2, true) . '</p>',
            'topics' => ['test'],
            'quality_score' => $quality,
        ];
    }
}
