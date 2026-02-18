<?php

it('provides default config values', function () {
    expect(config('northcloud.redis.connection'))->toBe('northcloud');
    expect(config('northcloud.redis.channels'))->toBeArray();
    expect(config('northcloud.quality.min_score'))->toBe(0);
    expect(config('northcloud.models.article'))->toBe(\JonesRussell\NorthCloud\Models\Article::class);
    expect(config('northcloud.processors'))->toBeArray();
    expect(config('northcloud.processing.sync'))->toBeTrue();
    expect(config('northcloud.content.allowed_tags'))->toBeArray();
    expect(config('northcloud.tags.default_type'))->toBe('topic');
});

it('provides default mail config values', function () {
    expect(config('northcloud.mail.sendgrid.api_key'))->toBeNull();
    expect(config('northcloud.mail.sendgrid.set_as_default'))->toBeTrue();
});
