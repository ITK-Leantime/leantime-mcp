<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp;

use Laravel\Mcp\Server;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\AddComment;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\CreateTodo;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\GetProjectProgress;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\GetTodo;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\ListAttachments;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\ListComments;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\ListMilestones;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\ListProjects;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\ListStatuses;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\ListTimeEntries;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\ListTodos;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\LogTime;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\Ping;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\SetTodoStatus;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\UpdateTodo;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\WhoAmI;

/**
 * The ITK MCP server, served at the route registered in routes.php.
 *
 * Named LeantimeMcpServer for this plugin, but deliberately NOT mounted on /mcp: Leantime
 * core reserves that path for its own commercial McpServer plugin (whose server class is
 * also called LeantimeMcpServer). Two servers on one path would resolve by route order.
 *
 * Only what is listed here is callable — there is deliberately no generic "call any service"
 * tool, which would hand back the full API surface this plugin exists to narrow. There is
 * also no tool that deletes anything: deletion is unreachable by construction rather than
 * guarded by a warning in a description.
 */
class LeantimeMcpServer extends Server
{
    /**
     * The path this server is served from, without a leading slash.
     *
     * Single source of truth: routes.php mounts it here and register.php exempts the same
     * value from core's AuthCheck. The two must not drift, or the endpoint either 302s to the
     * login page or is left unauthenticated.
     */
    public const ROUTE = 'mcp-itk';

    /**
     * Reported to clients in initialize's serverInfo.
     *
     * Distinguishes this from Leantime's own commercial MCP server on /mcp, which reports
     * itself as "Leantime" — a client talking to both should be able to tell them apart.
     */
    public string $serverName = 'Leantime MCP (ITK)';

    public string $serverVersion = '0.2.0';

    /**
     * Shown to clients on initialize, so keep it describing what these tools reach.
     */
    public string $instructions = 'Read and update Leantime projects, todos, comments and time entries. '
        .'Access is limited to the projects the calling API key is granted.';

    /**
     * The tools exposed by this server.
     *
     * Read tools are annotated #[IsReadOnly] on the class; write tools assert the API key's
     * write grant in handle(). None of them delete.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    public array $tools = [
        // Diagnostics.
        Ping::class,
        WhoAmI::class,

        // Reading projects.
        ListProjects::class,
        GetProjectProgress::class,
        ListStatuses::class,
        ListMilestones::class,

        // Reading todos and their context.
        ListTodos::class,
        GetTodo::class,
        ListComments::class,
        ListAttachments::class,
        ListTimeEntries::class,

        // Writing. Each asserts the write grant before touching anything, and none delete.
        CreateTodo::class,
        UpdateTodo::class,
        SetTodoStatus::class,
        LogTime::class,
        AddComment::class,
    ];
}
