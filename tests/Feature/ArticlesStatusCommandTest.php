<?php

use JonesRussell\NorthCloud\Models\Article;

it('registers the articles:status command', function () {
    $this->artisan('list')
        ->expectsOutputToContain('articles:status');
});

it('displays configured channels', function () {
    config(['northcloud.redis.channels' => ['crime:homepage', 'crime:courts']]);

    $this->artisan('articles:status')
        ->expectsOutputToContain('crime:homepage')
        ->expectsOutputToContain('crime:courts');
});

it('displays recent activity counts', function () {
    Article::factory()->count(5)->create([
        'created_at' => now()->subHours(2),
    ]);
    Article::factory()->count(3)->create([
        'created_at' => now()->subDays(2),
    ]);

    $this->artisan('articles:status')
        ->expectsOutputToContain('5');
});

it('displays processing mode', function () {
    config(['northcloud.processing.sync' => true]);

    $this->artisan('articles:status')
        ->expectsOutputToContain('sync');
});
