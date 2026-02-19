<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use JonesRussell\NorthCloud\Events\ArticleReceived;
use JonesRussell\NorthCloud\Jobs\ProcessIncomingArticle;

class SubscribeToArticleFeed extends Command
{
    protected $signature = 'articles:subscribe
        {--channels= : Comma-separated channels (overrides config)}
        {--connection= : Redis connection name (overrides config)}
        {--detailed : Show detailed output}';

    protected $description = 'Subscribe to North Cloud Redis pub/sub for incoming articles';

    protected bool $shouldStop = false;

    protected int $processedCount = 0;

    protected int $skippedCount = 0;

    protected int $errorCount = 0;

    public function handle(): int
    {
        $channels = $this->resolveChannels();
        $connection = $this->option('connection') ?? config('northcloud.redis.connection', 'northcloud');

        $this->info("Connection: {$connection}");
        $this->info('Subscribing to channels: '.implode(', ', $channels));

        $this->registerSignalHandlers();

        $redisConfig = config("database.redis.{$connection}");

        if (! $redisConfig) {
            $this->error("Redis connection [{$connection}] not configured in database.redis.");

            return self::FAILURE;
        }

        while (! $this->shouldStop) {
            try {
                $client = $this->createRedisClient($redisConfig);

                $client->subscribe($channels, function (\Redis $redis, string $channel, string $message) {
                    $this->processMessage($message);
                });
            } catch (\RedisException $e) {
                try {
                    $client->close();
                } catch (\Throwable) {
                    // Ignore close errors on broken connection
                }

                if ($this->shouldStop) {
                    break;
                }

                $msg = $e->getMessage();
                if (str_contains($msg, 'read error') || str_contains($msg, 'timed out')) {
                    continue;
                }

                $this->error("Redis error: {$msg}. Reconnecting in 5s...");
                Log::error('Redis subscriber error', ['error' => $msg]);
                sleep(5);
            } catch (\Exception $e) {
                try {
                    $client->close();
                } catch (\Throwable) {
                    // Ignore close errors on broken connection
                }

                if ($this->shouldStop) {
                    break;
                }

                $this->error("Unexpected error: {$e->getMessage()}. Reconnecting in 5s...");
                Log::error('Subscriber unexpected error', ['error' => $e->getMessage()]);
                sleep(5);
            }
        }

        $this->displaySummary();

        return self::SUCCESS;
    }

    protected function resolveChannels(): array
    {
        if ($this->input && ($channels = $this->option('channels'))) {
            return array_map('trim', explode(',', $channels));
        }

        $channels = config('northcloud.redis.channels', []);

        if ($channels === [] && config('northcloud.redis.channel')) {
            return [config('northcloud.redis.channel')];
        }

        return $channels ?: ['articles:default'];
    }

    protected function processMessage(string $message): void
    {
        try {
            $data = json_decode($message, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to decode Redis message', [
                    'error' => json_last_error_msg(),
                    'message_preview' => substr($message, 0, 200),
                ]);
                $this->errorCount++;

                return;
            }

            if (! $this->isValidMessage($data)) {
                Log::warning('Invalid article message format', ['data_keys' => array_keys($data)]);
                $this->errorCount++;

                return;
            }

            // Quality filter
            $qualityEnabled = config('northcloud.quality.enabled', false);
            $minScore = config('northcloud.quality.min_score', 0);

            if ($qualityEnabled && $minScore > 0 && ($data['quality_score'] ?? 0) < $minScore) {
                $this->skippedCount++;

                if ($this->input && $this->option('detailed')) {
                    $this->line("  Skipped (quality {$data['quality_score']} < {$minScore}): {$data['title']}");
                }

                return;
            }

            ArticleReceived::dispatch($data, $data['publisher']['channel'] ?? 'unknown');

            $sync = config('northcloud.processing.sync', true);
            if ($sync) {
                ProcessIncomingArticle::dispatchSync($data);
            } else {
                ProcessIncomingArticle::dispatch($data);
            }

            $this->processedCount++;

            if ($this->input && $this->option('detailed')) {
                $title = $data['title'] ?? $data['og_title'] ?? 'Untitled';
                $this->info("  Processed: {$title}");
            }
        } catch (\Exception $e) {
            $this->errorCount++;
            Log::error('Failed to process message', [
                'error' => $e->getMessage(),
                'message_preview' => substr($message, 0, 200),
            ]);
        }
    }

    protected function isValidMessage(array $data): bool
    {
        return isset($data['id']) && isset($data['title']);
    }

    protected function createRedisClient(array $config): \Redis
    {
        $client = new \Redis;

        $host = $config['host'] ?? '127.0.0.1';
        $port = (int) ($config['port'] ?? 6379);
        $password = $config['password'] ?? null;

        $client->connect($host, $port);

        if ($password) {
            $client->auth($password);
        }

        $readTimeout = $config['read_timeout'] ?? -1;
        $client->setOption(\Redis::OPT_READ_TIMEOUT, (float) $readTimeout);

        return $client;
    }

    protected function registerSignalHandlers(): void
    {
        if (! extension_loaded('pcntl')) {
            return;
        }

        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, function () {
            $this->info('Received SIGTERM, shutting down gracefully...');
            $this->shouldStop = true;
        });

        pcntl_signal(SIGINT, function () {
            $this->info('Received SIGINT, shutting down gracefully...');
            $this->shouldStop = true;
        });
    }

    protected function displaySummary(): void
    {
        $this->newLine();
        $this->info('Subscriber shutdown summary:');
        $this->info("  Processed: {$this->processedCount}");
        $this->info("  Skipped:   {$this->skippedCount}");
        $this->info("  Errors:    {$this->errorCount}");
    }
}
