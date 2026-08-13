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
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    public function name(): string
    {
        return 'list_todos';
    }

    public function description(): string
    {
        return 'Lists todos assigned to a person, newest ids last. Results only ever include '
            .'projects this API key may access.';
    }

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
            ->description('Maximum todos to return (default 50).')
            ->integer('sinceId')
            ->description('Return todos with an id at or above this, for paging.');
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return list<mixed>
     */
    protected function run(array $arguments): array
    {
        $input = array_filter([
            'username' => $this->requireArg($arguments, 'username'),
            'status' => $arguments['status'] ?? null,
            'dateFrom' => $arguments['dueFrom'] ?? null,
            'dateTo' => $arguments['dueTo'] ?? null,
            'limit' => (string) (int) ($arguments['limit'] ?? 50),
            'sinceId' => (string) (int) ($arguments['sinceId'] ?? 0),
        ], fn ($value) => $value !== null);

        return $this->gateway->results(
            fn ($controller, $apiUser) => $controller->tickets($input, $apiUser)
        );
    }
}
