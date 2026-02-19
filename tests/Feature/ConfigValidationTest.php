<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;

it('warns when singular channel key is used', function () {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $msg) => str_contains($msg, 'northcloud.redis.channel'));

    config()->set('northcloud.redis.channel', 'articles:test');
    config()->offsetUnset('northcloud.redis.channels');

    app(\JonesRussell\NorthCloud\Support\ConfigValidator::class)->validate();
});

it('warns when singular processor key is used', function () {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $msg) => str_contains($msg, 'northcloud.processing.processor'));

    config()->set('northcloud.processing.processor', 'SomeClass');

    app(\JonesRussell\NorthCloud\Support\ConfigValidator::class)->validate();
});

it('warns about unknown config keys', function () {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $msg) => str_contains($msg, 'content.max_excerpt_length'));

    config()->set('northcloud.content.max_excerpt_length', 500);

    app(\JonesRussell\NorthCloud\Support\ConfigValidator::class)->validate();
});

it('does not warn when config is correct', function () {
    Log::shouldReceive('warning')->never();

    app(\JonesRussell\NorthCloud\Support\ConfigValidator::class)->validate();
});
