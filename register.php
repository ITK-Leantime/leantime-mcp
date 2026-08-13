<?php

use Leantime\Core\Events\EventDispatcher;
use Leantime\Plugins\LeantimeMcp\Mcp\LeantimeMcpServer;

/*
 * Wires this plugin's MCP server into Leantime.
 *
 * Since Leantime 3.9.7 core depends on laravel/mcp, whose service provider is auto-discovered,
 * so there is nothing to register by hand: the server itself is mounted as a route in
 * routes.php, and the tool list lives on LeantimeMcpServer.
 *
 * Load order: plugins load in DB order with no dependency graph, so this file MUST NOT touch
 * other plugins' classes (notably Databridge). Tools resolve their services lazily at request
 * time instead — by then every plugin has loaded.
 */

/*
 * Exempt our MCP route from core AuthCheck: clients authenticate with a Databridge API key on
 * the request itself, not a Leantime session, so without this they would be redirected to the
 * login page.
 *
 * Core turns the path into a dot-separated route ('mcp/itk' -> 'mcp.itk') and compares the
 * first two segments, so the exemption is the route with slashes swapped for dots. Derived from
 * the constant rather than written out, so changing the path cannot leave this behind.
 *
 * This makes the route public as far as core is concerned — authentication is enforced by the
 * ApiKeyAuth middleware in routes.php. Fail-closed: when this plugin is disabled neither file
 * loads, so the route does not exist at all.
 */
EventDispatcher::add_filter_listener(
    'leantime.core.middleware.authcheck.__construct.publicActions',
    function (array $publicActions): array {
        $route = str_replace('/', '.', LeantimeMcpServer::ROUTE);

        if (! in_array($route, $publicActions, true)) {
            $publicActions[] = $route;
        }

        return $publicActions;
    }
);

/*
 * No rate-limit wiring is needed: because ROUTE sits under /mcp/, core's isMcpRequest() already
 * matches it, so RequestRateLimiter applies the MCP budget (LEAN_RATELIMIT_MCP) on its own.
 */
