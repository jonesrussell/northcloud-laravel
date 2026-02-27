<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Mcp\Tools;

use JonesRussell\NorthCloud\Services\ProductionSshService;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

class ProductionArtisanTool extends Tool
{
    public function __construct(
        protected ProductionSshService $sshService
    ) {}

    public function description(): string
    {
        return 'Run a Laravel artisan command on the production server. Examples: "migrate --force", "cache:clear", "tmdb:import --limit=100 --sync"';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->string('command')
            ->description('The artisan command to run (without "php artisan" prefix). Examples: "migrate", "cache:clear", "tmdb:import".')
            ->required()
            ->string('arguments')
            ->description('Optional arguments and flags for the command. Examples: "--force", "--limit=100 --sync".')
            ->optional();
    }

    public function handle(array $arguments): ToolResult
    {
        $command = trim($arguments['command'] ?? '');
        $args = trim($arguments['arguments'] ?? '');

        if (empty($command)) {
            return ToolResult::error('You must provide an artisan command to run (e.g., "migrate", "cache:clear", "tmdb:import").');
        }

        if (strlen($command) > 500) {
            return ToolResult::error('Command name must be 500 characters or less.');
        }

        if (strlen($args) > 1000) {
            return ToolResult::error('Arguments must be 1000 characters or less.');
        }

        if (str_starts_with($command, 'artisan ')) {
            $command = substr($command, 8);
        }
        if (str_starts_with($command, 'php artisan ')) {
            $command = substr($command, 12);
        }

        if (! $this->sshService->isConfigured()) {
            return ToolResult::error('Production SSH is not configured. Set NORTHCLOUD_PRODUCTION_HOST and NORTHCLOUD_PRODUCTION_PATH environment variables.');
        }

        try {
            $result = $this->sshService->artisan($command, $args);

            $fullCommand = "php artisan {$command}";
            if (! empty($args)) {
                $fullCommand .= " {$args}";
            }

            $output = "Command: {$fullCommand}\n";
            $output .= "Exit code: {$result['exit_code']}\n\n";

            if (! empty($result['stdout'])) {
                $output .= "--- OUTPUT ---\n{$result['stdout']}\n";
            }

            if (! empty($result['stderr'])) {
                $output .= "--- ERRORS ---\n{$result['stderr']}\n";
            }

            if (empty($result['stdout']) && empty($result['stderr'])) {
                $output .= "(no output)\n";
            }

            return ToolResult::text($output);
        } catch (\Exception $e) {
            return ToolResult::error("Artisan command error: {$e->getMessage()}");
        }
    }
}
