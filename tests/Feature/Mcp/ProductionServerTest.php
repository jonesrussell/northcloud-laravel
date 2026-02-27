<?php

declare(strict_types=1);

use JonesRussell\NorthCloud\Mcp\Tools\ProductionArtisanTool;
use JonesRussell\NorthCloud\Mcp\Tools\ProductionDbQueryTool;
use JonesRussell\NorthCloud\Mcp\Tools\ProductionSshTool;
use JonesRussell\NorthCloud\Services\ProductionSshService;

use function Pest\Laravel\mock;

beforeEach(function () {
    config(['northcloud.mcp.enabled' => true]);
    config(['northcloud.mcp.production.host' => 'test.example.com']);
    config(['northcloud.mcp.production.deploy_path' => '/var/www/app']);
});

describe('ProductionSshService', function () {
    it('reports not configured when host is empty', function () {
        config(['northcloud.mcp.production.host' => '']);

        $service = new ProductionSshService;

        expect($service->isConfigured())->toBeFalse();
    });

    it('reports not configured when deploy path is empty', function () {
        config(['northcloud.mcp.production.deploy_path' => '']);

        $service = new ProductionSshService;

        expect($service->isConfigured())->toBeFalse();
    });

    it('reports configured when host and deploy path are set', function () {
        $service = new ProductionSshService;

        expect($service->isConfigured())->toBeTrue();
    });

    it('returns correct host', function () {
        $service = new ProductionSshService;

        expect($service->getHost())->toBe('test.example.com');
    });

    it('returns correct deploy path', function () {
        $service = new ProductionSshService;

        expect($service->getDeployPath())->toBe('/var/www/app');
    });
});

describe('ProductionSshTool', function () {
    it('returns error when not configured', function () {
        config(['northcloud.mcp.production.host' => '']);

        $sshService = new ProductionSshService;
        $tool = new ProductionSshTool($sshService);

        $result = $tool->handle('ls -la');

        expect($result->isError())->toBeTrue();
    });

    it('returns error when command is empty', function () {
        $sshService = new ProductionSshService;
        $tool = new ProductionSshTool($sshService);

        $result = $tool->handle('');

        expect($result->isError())->toBeTrue();
    });

    it('executes command via SSH service', function () {
        $mockService = mock(ProductionSshService::class);
        $mockService->shouldReceive('isConfigured')->andReturn(true);
        $mockService->shouldReceive('runCommand')
            ->with('ls -la')
            ->andReturn([
                'stdout' => "total 0\ndrwxr-xr-x 2 user user 40 Jan 1 00:00 .\n",
                'stderr' => '',
                'exit_code' => 0,
            ]);

        $tool = new ProductionSshTool($mockService);
        $result = $tool->handle('ls -la');

        expect($result->isError())->toBeFalse();
        expect((string) $result->content())->toContain('Exit code: 0');
        expect((string) $result->content())->toContain('drwxr-xr-x');
    });
});

describe('ProductionDbQueryTool', function () {
    it('rejects non-SELECT queries', function () {
        $sshService = new ProductionSshService;
        $tool = new ProductionDbQueryTool($sshService);

        $result = $tool->handle('DELETE FROM users');

        expect($result->isError())->toBeTrue();
    });

    it('allows SELECT queries and returns JSON', function () {
        $mockService = mock(ProductionSshService::class);
        $mockService->shouldReceive('isConfigured')->andReturn(true);
        $mockService->shouldReceive('dbQuery')
            ->with('SELECT * FROM movies LIMIT 5')
            ->andReturn([
                'stdout' => '[{"id":1,"title":"Apocalypse Now"},{"id":2,"title":"Saving Private Ryan"}]',
                'stderr' => '',
                'exit_code' => 0,
            ]);

        $tool = new ProductionDbQueryTool($mockService);
        $result = $tool->handle('SELECT * FROM movies LIMIT 5');

        expect($result->isError())->toBeFalse();
        expect((string) $result->content())->toContain('Apocalypse Now');
    });

    it('handles empty results', function () {
        $mockService = mock(ProductionSshService::class);
        $mockService->shouldReceive('isConfigured')->andReturn(true);
        $mockService->shouldReceive('dbQuery')
            ->with('SELECT * FROM movies WHERE id = 999999')
            ->andReturn([
                'stdout' => '[]',
                'stderr' => '',
                'exit_code' => 0,
            ]);

        $tool = new ProductionDbQueryTool($mockService);
        $result = $tool->handle('SELECT * FROM movies WHERE id = 999999');

        expect($result->isError())->toBeFalse();
        expect((string) $result->content())->toContain('no results');
    });
});

describe('ProductionArtisanTool', function () {
    it('runs artisan command via SSH service', function () {
        $mockService = mock(ProductionSshService::class);
        $mockService->shouldReceive('isConfigured')->andReturn(true);
        $mockService->shouldReceive('artisan')
            ->with('cache:clear', '')
            ->andReturn([
                'stdout' => "Application cache cleared!\n",
                'stderr' => '',
                'exit_code' => 0,
            ]);

        $tool = new ProductionArtisanTool($mockService);
        $result = $tool->handle('cache:clear');

        expect($result->isError())->toBeFalse();
        expect((string) $result->content())->toContain('Application cache cleared!');
    });

    it('passes arguments to artisan command', function () {
        $mockService = mock(ProductionSshService::class);
        $mockService->shouldReceive('isConfigured')->andReturn(true);
        $mockService->shouldReceive('artisan')
            ->with('tmdb:import', '--limit=100 --sync')
            ->andReturn([
                'stdout' => "Imported 100 movies.\n",
                'stderr' => '',
                'exit_code' => 0,
            ]);

        $tool = new ProductionArtisanTool($mockService);
        $result = $tool->handle('tmdb:import', '--limit=100 --sync');

        expect($result->isError())->toBeFalse();
        expect((string) $result->content())->toContain('Imported 100 movies');
    });

    it('strips php artisan prefix from command', function () {
        $mockService = mock(ProductionSshService::class);
        $mockService->shouldReceive('isConfigured')->andReturn(true);
        $mockService->shouldReceive('artisan')
            ->with('migrate', '--force')
            ->andReturn([
                'stdout' => "Nothing to migrate.\n",
                'stderr' => '',
                'exit_code' => 0,
            ]);

        $tool = new ProductionArtisanTool($mockService);
        $result = $tool->handle('php artisan migrate', '--force');

        expect($result->isError())->toBeFalse();
    });
});
