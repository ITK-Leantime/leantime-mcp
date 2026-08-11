<?php

use Leantime\Core\Events\EventDispatcher;
use PhpMcp\Laravel\McpServiceProvider;

/*
 * Wires php-mcp/laravel into Leantime to serve MCP over Streamable HTTP at /mcp.
 *
 * Leantime has no config/providers.php and a custom Application/HttpKernel, so the
 * provider is registered by hand here and its config is seeded in-process rather than
 * published to a config file.
 *
 * Load order: plugins load in DB order with no dependency graph, so this file MUST NOT
 * touch other plugins' classes (notably Databridge). Tools resolve their services lazily
 * at request time instead — by then every plugin has loaded.
 */

/*
 * Start from the vendor defaults, then override.
 *
 * The provider's register() calls mergeConfigFrom(), which is a SHALLOW array_merge at the
 * top-level 'mcp' key with existing values winning. So seeding a nested subtree (e.g.
 * mcp.discovery.*) before that merge would replace vendor's whole subtree and silently drop
 * its sibling defaults — save_to_cache, cors_origin, event_store and friends. Loading the
 * vendor array here and merging our overrides into it keeps every sibling intact.
 */
$vendorConfig = require __DIR__.'/../../../vendor/php-mcp/laravel/config/mcp.php';

config(['mcp' => $vendorConfig]);

// php-mcp reads config in the provider's register()/boot(), so seed it first.
config([
    // Route prefix 'mcp' matches the path core already reserves in IncomingRequest.
    'mcp.transports.http_integrated.enabled' => true,
    'mcp.transports.http_integrated.route_prefix' => 'mcp',

    /*
     * Leantime's 'web'/'api' middleware groups are empty — core middleware (including
     * AuthCheck) runs globally instead, and /mcp is exempted from it below. So this
     * middleware IS the authentication for /mcp: without it the endpoint is wide open.
     *
     * Reuses Databridge's ApiKeyAuth (class string only — no Databridge class is loaded at
     * registration time, keeping the load-order rule intact; the container resolves it per
     * request). ':read' is the floor to open a session at all; tools requiring mutation
     * re-check for the write grant on the authenticated ApiUser.
     */
    'mcp.transports.http_integrated.middleware' => [
        \Leantime\Plugins\Databridge\Middleware\ApiKeyAuth::class.':read',
    ],

    // stdio/dedicated transports are for standalone processes; we only serve over HTTP.
    'mcp.transports.stdio.enabled' => false,
    'mcp.transports.http_dedicated.enabled' => false,

    // Tool definitions live in this plugin, not the stock routes/mcp.php.
    'mcp.discovery.definitions_file' => __DIR__.'/mcp.php',

    /*
     * Attribute discovery scans the filesystem and rewrites the registry cache on every
     * request. Tools are registered explicitly in mcp.php, so turn it off.
     *
     * The key is auto_discover — the provider gates on mcp.discovery.auto_discover, and a
     * plausible-looking 'discovery.enabled' would be read by nothing and silently leave
     * discovery on.
     */
    'mcp.discovery.auto_discover' => false,

    'mcp.server.name' => 'Leantime MCP',
    'mcp.server.version' => '0.1.0',

    // Only tools are exposed for now; no resources or prompts.
    'mcp.capabilities.resources' => false,
    'mcp.capabilities.resourcesSubscribe' => false,
    'mcp.capabilities.prompts' => false,

    // Sessions default to the cache store; Leantime already runs Redis.
    'mcp.session.driver' => 'cache',

    /*
     * php-mcp emits this header itself (overriding core's) and falls back to '*' when the
     * key is absent. MCP clients are servers and CLIs, not browsers, so no origin should be
     * allowed: 'null' matches no origin, where '*' would invite any page to try. Not a
     * live hole today — auth is a custom x-api-key header, which a browser cannot attach
     * ambiently without a successful preflight — but the wildcard buys nothing here.
     *
     * Override via LEAN_MCP_CORS_ORIGIN if a browser-based client ever needs access.
     */
    'mcp.transports.http_integrated.cors_origin' => env('LEAN_MCP_CORS_ORIGIN', 'null'),
]);

app()->register(McpServiceProvider::class);

/*
 * Exempt /mcp from core AuthCheck: MCP clients authenticate with a Databridge API key on
 * the request itself, not a Leantime session. Core matches public routes on the first two
 * dot-separated route segments, so 'mcp' covers the whole prefix.
 *
 * This makes /mcp public as far as core is concerned — authentication is enforced by the
 * plugin's own middleware. Fail-closed: when this plugin is disabled the file never loads
 * and core auth rejects /mcp instead.
 */
EventDispatcher::add_filter_listener(
    'leantime.core.middleware.authcheck.__construct.publicActions',
    function (array $publicActions): array {
        if (! in_array('mcp', $publicActions, true)) {
            $publicActions[] = 'mcp';
        }

        return $publicActions;
    }
);
