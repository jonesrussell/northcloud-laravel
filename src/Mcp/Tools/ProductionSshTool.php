<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Mcp\Tools;

use JonesRussell\NorthCloud\Services\ProductionSshService;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

class ProductionSshTool extends Tool
{
    public function __construct(
        protected ProductionSshService $sshService
    ) {}

    public function description(): string
    {
        return 'Run a shell command on the production server via SSH. Use for general server operations, checking logs, file operations, etc.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->string('command')
            ->description('The shell command to run on the production server.')
            ->required();
    }

    public function handle(array $arguments): ToolResult
    {
        $command = $arguments['command'] ?? '';

        if (empty($command)) {
            return ToolResult::error('You must provide a command to run on the production server.');
        }

        if (strlen($command) > 2000) {
            return ToolResult::error('Command must be 2000 characters or less for safety.');
        }

        if (! $this->sshService->isConfigured()) {
            return ToolResult::error('Production SSH is not configured. Set NORTHCLOUD_PRODUCTION_HOST and NORTHCLOUD_PRODUCTION_PATH environment variables.');
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

            return ToolResult::text($output);
        } catch (\Exception $e) {
            return ToolResult::error("SSH error: {$e->getMessage()}");
        }
    }
}
