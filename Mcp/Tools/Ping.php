<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Leantime\Plugins\LeantimeMcp\Mcp\LeantimeMcpServer;

/**
 * Connectivity check for this MCP server.
 *
 * Deliberately touches no Leantime data and no other plugin, so a failure here points at
 * the MCP transport itself rather than at data access or the auth bridge.
 */
#[IsReadOnly]
class Ping extends LeantimeTool
{
    /**
     * The tool name advertised in tools/list.
     *
     * @return string
     */
    public function name(): string
    {
        return 'ping';
    }

    /**
     * The tool description shown to agents in tools/list.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Confirms this Leantime MCP server is reachable and responding.';
    }

    /**
     * @param  array<string, mixed> $arguments
     * @return array{status: string, server: string}
     */
    protected function run(array $arguments): array
    {
        return [
            'status' => 'ok',
            // Read off the server rather than repeated here, so the two cannot disagree.
            'server' => (new LeantimeMcpServer())->serverName,
        ];
    }
}
