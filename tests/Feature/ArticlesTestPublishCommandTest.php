<?php

it('registers the articles:test-publish command', function () {
    $this->artisan('list')
        ->expectsOutputToContain('articles:test-publish');
});

it('generates a valid test article payload in dry run mode', function () {
    $this->artisan('articles:test-publish --dry-run')
        ->expectsOutputToContain('test-')
        ->expectsOutputToContain('Dry run')
        ->assertExitCode(0);
});

it('uses custom quality score', function () {
    $this->artisan('articles:test-publish --dry-run --quality=95')
        ->expectsOutputToContain('95')
        ->assertExitCode(0);
});
