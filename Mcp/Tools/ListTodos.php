<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Lists a person's todos, with optional date and status filtering.
 */
#[IsReadOnly]
class ListTodos extends LeantimeTool
{
    /**
     * Ceiling on rows per call.
     *
     * Databridge applies the limit verbatim, and the query builder drops the LIMIT clause
     * entirely for a negative value — so an agent passing -1 to mean "no limit" would pull
     * every assigned todo into one tool response. Clamped rather than rejected: the agent gets
     * usable data and can page with sinceId.
     */
    private const MAX_LIMIT = 200;

    /**
     * @param  DatabridgeGateway $gateway In-process bridge to Databridge's API controller.
     */
    public function __construct(private readonly DatabridgeGateway $gateway)
    {
    }

    /**
     * The tool name advertised in tools/list.
     *
     * @return string
     */
    public function name(): string
    {
        return 'list_todos';
    }

    /**
     * The tool description shown to agents in tools/list.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Lists todos assigned to a person, newest ids last. Results only ever include '
            . 'projects this API key may access.';
    }

    /**
     * Declares the tool's input arguments.
     *
     * @return ToolInputSchema
     */
    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->string('username')
            ->description('Email address of the person whose todos to list.')
            ->required()
            ->string('status')
            ->description('Filter by progress: NEW, INPROGRESS or DONE.')
            ->string('dueFrom')
            ->description('Only todos due on or after this date (YYYY-MM-DD).')
            ->string('dueTo')
            ->description('Only todos due on or before this date (YYYY-MM-DD).')
            ->integer('limit')
            ->description('Maximum todos to return, 1-' . self::MAX_LIMIT . ' (default 50).')
            ->integer('sinceId')
            ->description('Return todos with an id at or above this, for paging.');
    }

    /**
     * @param  array<string, mixed> $arguments
     * @return list<mixed>
     */
    protected function run(array $arguments): array
    {
        $input = array_filter([
            'username' => $this->requireArg($arguments, 'username'),
            'status' => $arguments['status'] ?? null,
            'dateFrom' => $arguments['dueFrom'] ?? null,
            'dateTo' => $arguments['dueTo'] ?? null,
            'limit' => (string) max(1, min((int) ($arguments['limit'] ?? 50), self::MAX_LIMIT)),
            // A negative id would widen the result set rather than narrow it.
            'sinceId' => (string) max(0, (int) ($arguments['sinceId'] ?? 0)),
        ], fn ($value) => $value !== null);

        return $this->gateway->results(
            fn ($controller, $apiUser) => $controller->tickets($input, $apiUser)
        );
    }
}
