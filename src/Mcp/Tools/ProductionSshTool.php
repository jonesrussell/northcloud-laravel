<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JonesRussell\NorthCloud\Services\ProductionSshService;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ProductionSshTool extends Tool
{
    public function __construct(
        protected ProductionSshService $sshService
    ) {}

    public function description(): string
    {
        return 'Run a shell command on the production server via SSH. Use for general server operations, checking logs, file operations, etc.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'command' => $schema->string()
                ->description('The shell command to run on the production server.')
                ->required(),
        ];
    }

    public function handle(string $command): Response
    {
        if (empty($command)) {
            return Response::error('You must provide a command to run on the production server.');
        }

        if (strlen($command) > 2000) {
            return Response::error('Command must be 2000 characters or less for safety.');
        }

        if (! $this->sshService->isConfigured()) {
            return Response::error('Production SSH is not configured. Set NORTHCLOUD_PRODUCTION_HOST and NORTHCLOUD_PRODUCTION_PATH environment variables.');
        }

        try {
            $result = $this->sshService->runCommand($command);

            $output = "Exit code: {$result['exit_code']}\n\n";

            if (! empty($result['stdout'])) {
                $output .= "--- STDOUT ---\n{$result['stdout']}\n";
            }

            if (! empty($result['stderr'])) {
                $output .= "--- STDERR ---\n{$result['stderr']}\n";
            }

            if (empty($result['stdout']) && empty($result['stderr'])) {
                $output .= "(no output)\n";
            }

            return Response::text($output);
        } catch (\Exception $e) {
            return Response::error("SSH error: {$e->getMessage()}");
        }
    }
}
