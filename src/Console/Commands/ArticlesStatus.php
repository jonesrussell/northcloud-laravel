<?php

namespace JonesRussell\NorthCloud\Console\Commands;

use Illuminate\Console\Command;

class ArticlesStatus extends Command
{
    protected $signature = 'articles:status';

    protected $description = 'Show North Cloud connection status and recent activity';

    public function handle(): int
    {
        $this->connectionStatus();
        $this->newLine();
        $this->recentActivity();

        return self::SUCCESS;
    }

    protected function connectionStatus(): void
    {
        $connection = config('northcloud.redis.connection', 'northcloud');
        $channels = config('northcloud.redis.channels', []);
        $qualityEnabled = config('northcloud.quality.enabled', false);
        $minScore = config('northcloud.quality.min_score', 0);
        $sync = config('northcloud.processing.sync', true);
        $articleModel = config('northcloud.models.article');
        $processors = config('northcloud.processors', []);

        $this->info('North Cloud Connection Status');
        $this->line(str_repeat('─', 40));

        $redisConfig = config("database.redis.{$connection}");
        if ($redisConfig) {
            $host = $redisConfig['host'] ?? '127.0.0.1';
            $port = $redisConfig['port'] ?? 6379;
            $this->line("Redis host:      {$host}:{$port}");

            try {
                $client = new \Redis;
                $start = microtime(true);
                $client->connect($host, (int) $port, 2);
                if ($password = $redisConfig['password'] ?? null) {
                    $client->auth($password);
                }
                $client->ping();
                $latency = round((microtime(true) - $start) * 1000);
                $this->line("Connection:      <fg=green>Connected</> (latency: {$latency}ms)");
                $client->close();
            } catch (\Exception $e) {
                $this->line('Connection:      <fg=yellow>Not tested</> (ext-redis not available or connection failed)');
            }
        } else {
            $this->line('Redis host:      <fg=red>Not configured</>');
        }

        $this->line('Channels:        '.count($channels).' configured');
        foreach ($channels as $channel) {
            $this->line("  - {$channel}");
        }

        if ($qualityEnabled) {
            $this->line("Quality filter:  enabled (min_score: {$minScore})");
        } else {
            $this->line('Quality filter:  disabled');
        }

        $this->line('Processing mode: '.($sync ? 'sync' : 'queued'));
        $this->line("Article model:   {$articleModel}");

        if (! empty($processors)) {
            $names = array_map(fn ($p) => class_basename($p), $processors);
            $this->line('Processors:      '.implode(' → ', $names));
        }
    }

    protected function recentActivity(): void
    {
        $articleModel = config('northcloud.models.article');

        $this->info('Recent Activity (last 24h)');
        $this->line(str_repeat('─', 40));

        $last24h = $articleModel::where('created_at', '>=', now()->subDay())->count();
        $last7d = $articleModel::where('created_at', '>=', now()->subWeek())->count();
        $total = $articleModel::count();

        $this->line("Articles (24h):  {$last24h}");
        $this->line("Articles (7d):   {$last7d}");
        $this->line("Articles total:  {$total}");

        $latest = $articleModel::latest('created_at')->first();
        if ($latest) {
            $ago = $latest->created_at->diffForHumans();
            $this->line("Last received:   {$ago}");
        } else {
            $this->line('Last received:   never');
        }
    }
}
