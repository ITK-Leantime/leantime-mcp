<?php

use Leantime\Plugins\LeantimeMcp\Mcp\Tools\Ping;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\WhoAmI;
use PhpMcp\Laravel\Facades\Mcp;

/*
 * MCP tool registry. Loaded by McpServiceProvider::loadMcpDefinitions() via the
 * mcp.discovery.definitions_file config set in register.php.
 *
 * Only what is registered here is callable — there is deliberately no generic
 * "call any service" tool, which would hand back the full API surface this plugin exists
 * to narrow.
 */

Mcp::tool('ping', Ping::class);
Mcp::tool('whoami', WhoAmI::class);
