<?php

it('registers all artisan commands', function () {
    $commands = ['articles:subscribe', 'articles:status', 'articles:stats', 'articles:test-publish', 'articles:replay'];

    foreach ($commands as $command) {
        $this->artisan('list')
            ->expectsOutputToContain($command);
    }
});

it('merges default config', function () {
    expect(config('northcloud'))->toBeArray();
    expect(config('northcloud.redis.connection'))->toBe('northcloud');
});

it('registers ArticleIngestionService as singleton', function () {
    $a = app(\JonesRussell\NorthCloud\Services\ArticleIngestionService::class);
    $b = app(\JonesRussell\NorthCloud\Services\ArticleIngestionService::class);

    expect($a)->toBe($b);
});
