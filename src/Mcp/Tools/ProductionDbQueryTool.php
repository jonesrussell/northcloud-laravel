<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JonesRussell\NorthCloud\Services\ProductionSshService;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ProductionDbQueryTool extends Tool
{
    public function __construct(
        protected ProductionSshService $sshService
    ) {}

    public function description(): string
    {
        return 'Query the production database via tinker. Only SELECT queries are allowed for safety. Works with any database (MySQL, MariaDB, PostgreSQL, SQLite).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('The SQL SELECT query to run on production database.')
                ->required(),
        ];
    }

    public function handle(string $query): Response
    {
        $query = trim($query);

        if (empty($query)) {
            return Response::error('You must provide a SQL query to run.');
        }

        if (strlen($query) > 5000) {
            return Response::error('Query must be 5000 characters or less.');
        }

        $upperQuery = strtoupper($query);

        if (! str_starts_with($upperQuery, 'SELECT')) {
            return Response::error('Only SELECT queries are allowed for safety. Use production-artisan-tool for write operations.');
        }

        if (! $this->sshService->isConfigured()) {
            return Response::error('Production SSH is not configured. Set NORTHCLOUD_PRODUCTION_HOST and NORTHCLOUD_PRODUCTION_PATH environment variables.');
        }

        try {
            $result = $this->sshService->dbQuery($query);

            if ($result['exit_code'] !== 0) {
                $errorMsg = ! empty($result['stderr']) ? $result['stderr'] : 'Query failed with no error message.';

                return Response::error("Database error: {$errorMsg}");
            }

            $output = trim($result['stdout']);
            if (empty($output) || $output === '[]') {
                return Response::text("(no results)\n");
            }

            $data = json_decode($output, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                return Response::json($data);
            }

            return Response::text($output);
        } catch (\Exception $e) {
            return Response::error("Database query error: {$e->getMessage()}");
        }
    }
}
