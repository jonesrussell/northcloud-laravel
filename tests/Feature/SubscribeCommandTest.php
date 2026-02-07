<?php

use JonesRussell\NorthCloud\Console\Commands\SubscribeToArticleFeed;
use JonesRussell\NorthCloud\Models\Article;

it('registers the articles:subscribe command', function () {
    $this->artisan('list')
        ->expectsOutputToContain('articles:subscribe');
});

it('resolves channels from config', function () {
    config(['northcloud.redis.channels' => ['crime:homepage', 'crime:courts']]);

    $command = app(SubscribeToArticleFeed::class);

    $reflection = new ReflectionMethod($command, 'resolveChannels');
    $channels = $reflection->invoke($command);

    expect($channels)->toBe(['crime:homepage', 'crime:courts']);
});

it('processes a valid message into an article', function () {
    $command = app(SubscribeToArticleFeed::class);

    $message = json_encode([
        'id' => 'cmd-test-001',
        'title' => 'Command Test Article',
        'canonical_url' => 'https://example.com/cmd-test',
        'publisher' => ['route_id' => 'r1', 'published_at' => '2026-01-15', 'channel' => 'test'],
        'body' => '<p>Content</p>',
    ]);

    $reflection = new ReflectionMethod($command, 'processMessage');
    $reflection->invoke($command, $message);

    expect(Article::count())->toBe(1);
    expect(Article::first()->title)->toBe('Command Test Article');
});

it('skips messages with low quality score when filter is enabled', function () {
    config([
        'northcloud.quality.enabled' => true,
        'northcloud.quality.min_score' => 60,
    ]);

    $command = app(SubscribeToArticleFeed::class);

    $message = json_encode([
        'id' => 'low-quality-001',
        'title' => 'Low Quality Article',
        'canonical_url' => 'https://example.com/low-quality',
        'publisher' => ['route_id' => 'r1', 'published_at' => '2026-01-15', 'channel' => 'test'],
        'quality_score' => 30,
    ]);

    $reflection = new ReflectionMethod($command, 'processMessage');
    $reflection->invoke($command, $message);

    expect(Article::count())->toBe(0);
});

it('rejects invalid JSON messages', function () {
    $command = app(SubscribeToArticleFeed::class);

    $reflection = new ReflectionMethod($command, 'processMessage');
    $reflection->invoke($command, 'not-json{{{');

    expect(Article::count())->toBe(0);
});
