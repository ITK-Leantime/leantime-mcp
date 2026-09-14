<?php

use Laravel\Mcp\Server\Facades\Mcp;
use Leantime\Plugins\LeantimeMcp\Mcp\LeantimeMcpServer;

/*
 * Serves this plugin's MCP server over Streamable HTTP.
 *
 * Loaded by Core\Routing\RouteLoader, which requires routes.php from every enabled plugin.
 *
 * Path is /mcp/itk, NOT /mcp: since 3.9.7 core reserves /mcp for Leantime's own commercial
 * McpServer plugin (see IncomingRequest::$apiEndpoints and isMcpRequest()). Both registering
 * POST /mcp would let route order decide which server answers. It stays under /mcp/ so core
 * still classifies it as MCP traffic and rate limits it — see the ROUTE constant.
 *
 * Leantime's 'web'/'api' middleware groups are empty — core middleware (including AuthCheck)
 * runs globally instead, and this route is exempted from it in register.php. So this
 * middleware IS the authentication: without it the endpoint is wide open.
 *
 * Reuses Databridge's ApiKeyAuth (class string only, so no Databridge class loads while
 * routes are being registered — the container resolves it per request). ':read' is the floor
 * to reach any tool at all; write tools re-check for the write grant on the ApiUser.
 */
Mcp::web(LeantimeMcpServer::ROUTE, LeantimeMcpServer::class)
    ->middleware([\Leantime\Plugins\Databridge\Middleware\ApiKeyAuth::class . ':read']);
