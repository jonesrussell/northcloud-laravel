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

it('registers sendgrid mail transport when API key is configured', function () {
    config(['northcloud.mail.sendgrid.api_key' => 'SG.test-key']);

    // Re-boot the service provider to pick up the config
    $provider = new \JonesRussell\NorthCloud\NorthCloudServiceProvider($this->app);
    $provider->boot();

    // Verify the mailer config was added
    expect(config('mail.mailers.sendgrid'))->toBe(['transport' => 'sendgrid']);
    expect(config('mail.default'))->toBe('sendgrid');
});

it('does not register sendgrid transport when API key is null', function () {
    config(['northcloud.mail.sendgrid.api_key' => null]);

    $provider = new \JonesRussell\NorthCloud\NorthCloudServiceProvider($this->app);
    $provider->boot();

    expect(config('mail.mailers.sendgrid'))->toBeNull();
});

it('does not override default mailer when set_as_default is false', function () {
    config([
        'northcloud.mail.sendgrid.api_key' => 'SG.test-key',
        'northcloud.mail.sendgrid.set_as_default' => false,
        'mail.default' => 'smtp',
    ]);

    $provider = new \JonesRussell\NorthCloud\NorthCloudServiceProvider($this->app);
    $provider->boot();

    expect(config('mail.mailers.sendgrid'))->toBe(['transport' => 'sendgrid']);
    expect(config('mail.default'))->toBe('smtp');
});
