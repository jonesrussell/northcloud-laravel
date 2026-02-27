<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Mcp\Tools;

use JonesRussell\NorthCloud\Services\ProductionSshService;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

class ProductionDbQueryTool extends Tool
{
    public function __construct(
        protected ProductionSshService $sshService
    ) {}

    public function description(): string
    {
        return 'Query the production database via tinker. Only SELECT queries are allowed for safety. Works with any database (MySQL, MariaDB, PostgreSQL, SQLite).';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->string('query')
            ->description('The SQL SELECT query to run on production database.')
            ->required();
    }

    public function handle(array $arguments): ToolResult
    {
        $query = trim($arguments['query'] ?? '');

        if (empty($query)) {
            return ToolResult::error('You must provide a SQL query to run.');
        }

        if (strlen($query) > 5000) {
            return ToolResult::error('Query must be 5000 characters or less.');
        }

        $upperQuery = strtoupper($query);

        if (! str_starts_with($upperQuery, 'SELECT')) {
            return ToolResult::error('Only SELECT queries are allowed for safety. Use production-artisan-tool for write operations.');
        }

        if (! $this->sshService->isConfigured()) {
            return ToolResult::error('Production SSH is not configured. Set NORTHCLOUD_PRODUCTION_HOST and NORTHCLOUD_PRODUCTION_PATH environment variables.');
        }

        try {
            $result = $this->sshService->dbQuery($query);

            if ($result['exit_code'] !== 0) {
                $errorMsg = ! empty($result['stderr']) ? $result['stderr'] : 'Query failed with no error message.';

                return ToolResult::error("Database error: {$errorMsg}");
            }

            $output = trim($result['stdout']);
            if (empty($output) || $output === '[]') {
                return ToolResult::text("(no results)\n");
            }

            $data = json_decode($output, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                return ToolResult::json($data);
            }

            return ToolResult::text($output);
        } catch (\Exception $e) {
            return ToolResult::error("Database query error: {$e->getMessage()}");
        }
    }
}
