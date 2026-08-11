<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

/**
 * Connectivity check for the Leantime MCP server.
 *
 * Deliberately touches no Leantime data and no other plugin, so a failure here points at
 * the MCP transport itself rather than at data access or the auth bridge.
 */
class Ping
{
    /**
     * Confirms the Leantime MCP server is reachable and responding.
     *
     * @return array{status: string, server: string} Liveness status and server name.
     */
    public function __invoke(): array
    {
        return [
            'status' => 'ok',
            'server' => config('mcp.server.name', 'Leantime MCP'),
        ];
    }
}
