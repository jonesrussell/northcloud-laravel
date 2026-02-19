<?php

declare(strict_types=1);

it('reports all clear when config is valid', function () {
    $this->artisan('northcloud:doctor')
        ->expectsOutputToContain('All checks passed')
        ->assertSuccessful();
});

it('reports deprecated channel key', function () {
    config()->set('northcloud.redis.channel', 'test');
    config()->offsetUnset('northcloud.redis.channels');

    $this->artisan('northcloud:doctor')
        ->expectsOutputToContain('redis.channel')
        ->assertFailed();
});
