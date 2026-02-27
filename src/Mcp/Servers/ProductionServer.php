<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Mcp\Servers;

use JonesRussell\NorthCloud\Mcp\Tools\ProductionArtisanTool;
use JonesRussell\NorthCloud\Mcp\Tools\ProductionDbQueryTool;
use JonesRussell\NorthCloud\Mcp\Tools\ProductionSshTool;
use Laravel\Mcp\Server;

class ProductionServer extends Server
{
    public string $serverName = 'NorthCloud Production';

    public string $serverVersion = '1.0.0';

    public string $instructions = <<<'INSTRUCTIONS'
This MCP server provides secure SSH access to your production server.

Available tools:
- production-ssh-tool: Run any shell command on the production server
- production-db-query-tool: Query the production SQLite database (SELECT/PRAGMA only)
- production-artisan-tool: Run Laravel artisan commands on production

Use these tools to:
- Check production database state
- Run artisan commands like migrations, cache clearing, or custom imports
- Debug production issues by checking logs or running diagnostic commands

IMPORTANT: Be careful with destructive commands. Always verify before running commands that modify data.
INSTRUCTIONS;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    public array $tools = [
        ProductionSshTool::class,
        ProductionDbQueryTool::class,
        ProductionArtisanTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    public array $resources = [];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    public array $prompts = [];
}
