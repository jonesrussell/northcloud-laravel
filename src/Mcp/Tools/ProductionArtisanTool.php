<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JonesRussell\NorthCloud\Services\ProductionSshService;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ProductionArtisanTool extends Tool
{
    public function __construct(
        protected ProductionSshService $sshService
    ) {}

    public function description(): string
    {
        return 'Run a Laravel artisan command on the production server. Examples: "migrate --force", "cache:clear", "tmdb:import --limit=100 --sync"';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'command' => $schema->string()
                ->description('The artisan command to run (without "php artisan" prefix). Examples: "migrate", "cache:clear", "tmdb:import".')
                ->required(),
            'arguments' => $schema->string()
                ->description('Optional arguments and flags for the command. Examples: "--force", "--limit=100 --sync".'),
        ];
    }

    public function handle(string $command, string $arguments = ''): Response
    {
        $command = trim($command);
        $arguments = trim($arguments);

        if (empty($command)) {
            return Response::error('You must provide an artisan command to run (e.g., "migrate", "cache:clear", "tmdb:import").');
        }

        if (strlen($command) > 500) {
            return Response::error('Command name must be 500 characters or less.');
        }

        if (strlen($arguments) > 1000) {
            return Response::error('Arguments must be 1000 characters or less.');
        }

        if (str_starts_with($command, 'artisan ')) {
            $command = substr($command, 8);
        }
        if (str_starts_with($command, 'php artisan ')) {
            $command = substr($command, 12);
        }

        if (! $this->sshService->isConfigured()) {
            return Response::error('Production SSH is not configured. Set NORTHCLOUD_PRODUCTION_HOST and NORTHCLOUD_PRODUCTION_PATH environment variables.');
        }

        try {
            $result = $this->sshService->artisan($command, $arguments);

            $fullCommand = "php artisan {$command}";
            if (! empty($arguments)) {
                $fullCommand .= " {$arguments}";
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

            return Response::text($output);
        } catch (\Exception $e) {
            return Response::error("Artisan command error: {$e->getMessage()}");
        }
    }
}
