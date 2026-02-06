<?php

namespace JonesRussell\NorthCloud;

use Illuminate\Support\ServiceProvider;

class NorthCloudServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/northcloud.php', 'northcloud');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/northcloud.php' => config_path('northcloud.php'),
            ], 'northcloud-config');
        }
    }
}
