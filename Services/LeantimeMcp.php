<?php

namespace Leantime\Plugins\LeantimeMcp\Services;

/**
 * Plugin service for LeantimeMcp.
 *
 * Leantime resolves Plugins\{Folder}\Services\{Folder} when installing or removing a
 * plugin (see Plugins::getPluginClassName), so this class must exist for the plugin to be
 * installable even though the MCP server needs no install-time setup: it creates no tables
 * and stores no state — the tool list lives on Mcp\LeantimeMcpServer and API users in
 * Databridge's auth file.
 */
class LeantimeMcp
{
    /**
     * Called on plugin install. Nothing to set up.
     *
     * @return bool
     */
    public function install(): bool
    {
        return true;
    }

    /**
     * Called on plugin uninstall. Nothing to tear down.
     *
     * @return bool
     */
    public function uninstall(): bool
    {
        return true;
    }
}
