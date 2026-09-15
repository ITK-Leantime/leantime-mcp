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
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\ListUsers;
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
     *
     * Must stay UNDER /mcp/. Core's IncomingRequest::isMcpRequest() matches '/mcp', '/mcp/*'
     * and '/mcp?*', and RequestRateLimiter returns early for anything it and
     * isApiOrCronRequest() both reject — so a sibling path like '/mcp-itk' is not rate limited
     * at all, while '/mcp/itk' gets the limiter and its higher MCP budget. It is still a
     * distinct route from core's own server, which is mounted on exactly '/mcp'.
     */
    public const ROUTE = 'mcp/itk';

    /**
     * Reported to clients in initialize's serverInfo.
     *
     * Distinguishes this from Leantime's own commercial MCP server on /mcp, which reports
     * itself as "Leantime" — a client talking to both should be able to tell them apart.
     *
     * @var string
     */
    public string $serverName = 'Leantime MCP (ITK)';

    public string $serverVersion = '0.3.0';

    /**
     * Advertise only what this server actually implements.
     *
     * The vendor default announces resources and prompts too; we register neither, so clients
     * would offer empty resource and prompt pickers. The old php-mcp config switched them off
     * explicitly and this keeps that behaviour.
     *
     * @var array<string, array<string, bool>>
     */
    public array $capabilities = [
        'tools' => [
            'listChanged' => false,
        ],
    ];

    /**
     * Return every tool on the first page of tools/list.
     *
     * The vendor default is 15, which would split our 17 tools across two pages. Following the
     * cursor is optional for clients, so on one that ignores it the last tool would simply not
     * exist. Keep this comfortably above the tool count.
     *
     * @var int
     */
    public int $defaultPaginationLength = 50;

    /**
     * Wires up the vendor server, then swaps in this plugin's tools/call handler.
     */
    public function __construct()
    {
        parent::__construct();

        /*
         * Swap in a tools/call handler that tolerates a missing 'arguments' key.
         *
         * Done via addMethod rather than by redeclaring $methods, which would replace the
         * parent's whole map and drop initialize, tools/list and the rest.
         */
        $this->addMethod('tools/call', CallToolWithOptionalArguments::class);
    }

    /**
     * Negotiate an unknown-newer protocol version down instead of refusing the connection.
     *
     * laravel/mcp v0.1.1 rejects any protocolVersion outside $supportedProtocolVersion with
     * -32602 'Unsupported protocol version'. The MCP spec says a server should instead answer
     * with a revision it does support and let the client decide. Real clients already ask for
     * newer ones: mcp-remote — and so Claude Desktop — requests 2025-11-25 and dies with a fatal
     * error, making this server unreachable from Desktop.
     *
     * Rewritten in the raw message because initialize is dispatched via a private method that
     * hardcodes `new Initialize`, so it cannot be replaced through addMethod(). Only a request
     * NEWER than everything we support is stepped down; older or unrecognised revisions still hit
     * the vendor's error, which is the right answer for a genuinely incompatible client.
     *
     * Rewriting the request (rather than whitelisting the newer string) matters because
     * Initialize echoes the requested version straight back into its response — whitelisting
     * would have this server claim to speak a revision it does not implement.
     *
     * Remove once the vendor negotiates versions itself (tracked upstream as plugins#60).
     *
     * @return mixed Whatever the vendor's handle() returns — it declares no type either.
     */
    public function handle(string $rawMessage)
    {
        return parent::handle($this->negotiateProtocolVersion($rawMessage));
    }

    /**
     * Clamp an initialize request's protocolVersion to our newest supported revision.
     *
     * @return string The raw message, its protocolVersion rewritten when newer than supported.
     */
    private function negotiateProtocolVersion(string $rawMessage): string
    {
        $decoded = json_decode($rawMessage, true);

        if (! is_array($decoded) || ($decoded['method'] ?? null) !== 'initialize') {
            return $rawMessage;
        }

        $requested = $decoded['params']['protocolVersion'] ?? null;

        if (! is_string($requested) || in_array($requested, $this->supportedProtocolVersion, true)) {
            return $rawMessage;
        }

        /*
         * Only step down a well-formed, date-stamped revision. Without the shape check any
         * non-date string ('bogus') would sort above our newest and be quietly accepted, when the
         * vendor's 'Unsupported protocol version' is the correct answer for it.
         */
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $requested) !== 1) {
            return $rawMessage;
        }

        // Revisions are date-stamped (YYYY-MM-DD), so a string compare orders them correctly.
        $newest = max($this->supportedProtocolVersion);

        if ($requested <= $newest) {
            return $rawMessage;
        }

        $decoded['params']['protocolVersion'] = $newest;

        // Fall back to the original message if re-encoding somehow fails, rather than dropping it.
        return json_encode($decoded) ?: $rawMessage;
    }

    /**
     * Shown to clients on initialize, so keep it describing what these tools reach.
     *
     * @var string
     */
    public string $instructions = 'Read and update Leantime projects, todos, comments and time entries. '
        . 'Access is limited to the projects the calling API key is granted.';

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

        // Reading projects and the people on them.
        ListProjects::class,
        ListUsers::class,
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
