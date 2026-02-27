<?php

declare(strict_types=1);

use JonesRussell\NorthCloud\Mcp\Servers\ProductionServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('northcloud-production', ProductionServer::class);
