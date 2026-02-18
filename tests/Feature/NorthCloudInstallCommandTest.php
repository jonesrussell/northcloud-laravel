<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Clean up any previously published files
    $uiPath = resource_path('js/components/ui');
    if (File::isDirectory($uiPath)) {
        File::deleteDirectory($uiPath);
    }

    $configPath = config_path('northcloud.php');
    if (File::exists($configPath)) {
        File::delete($configPath);
    }
});

it('runs successfully', function () {
    $this->artisan('northcloud:install')
        ->assertSuccessful();
});

it('is registered and discoverable', function () {
    $this->artisan('list')
        ->expectsOutputToContain('northcloud:install');
});

it('publishes config file', function () {
    $this->artisan('northcloud:install');

    expect(File::exists(config_path('northcloud.php')))->toBeTrue();
});

it('publishes UI components', function () {
    $this->artisan('northcloud:install');

    $components = ['badge', 'button', 'card', 'checkbox', 'dialog', 'input', 'label', 'select'];

    foreach ($components as $component) {
        expect(File::exists(resource_path("js/components/ui/{$component}/index.ts")))
            ->toBeTrue("Expected UI component {$component}/index.ts to exist");
    }
});

it('skips UI components with --skip-ui', function () {
    $this->artisan('northcloud:install', ['--skip-ui' => true]);

    expect(File::isDirectory(resource_path('js/components/ui')))->toBeFalse();
});

it('overwrites existing files with --force', function () {
    // First install
    $this->artisan('northcloud:install');

    $configPath = config_path('northcloud.php');
    expect(File::exists($configPath))->toBeTrue();

    // Write a marker to the config file
    File::put($configPath, '<?php return ["marker" => true];');

    // Second install with --force
    $this->artisan('northcloud:install', ['--force' => true]);

    // File should be overwritten (no longer contain our marker)
    $contents = File::get($configPath);
    expect($contents)->not->toContain("'marker' => true");
});
