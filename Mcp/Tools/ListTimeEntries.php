<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;
use RuntimeException;

/**
 * Lists logged time entries.
 */
#[IsReadOnly]
class ListTimeEntries extends LeantimeTool
{
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    public function name(): string
    {
        return 'list_time_entries';
    }

    public function description(): string
    {
        return 'Lists time logged against a todo or across a project, with hours and work dates. '
            .'Pass exactly one of todoId or projectId.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('todoId')
            ->description('Restrict to one todo.')
            ->integer('projectId')
            ->description('Restrict to one project.');
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return list<mixed>
     */
    protected function run(array $arguments): array
    {
        $todoId = $arguments['todoId'] ?? null;
        $projectId = $arguments['projectId'] ?? null;

        // Databridge requires exactly one of the two, so reject both-given here as well as
        // neither-given rather than letting the endpoint refuse it a layer later.
        if (($todoId === null) === ($projectId === null)) {
            throw new RuntimeException('Pass exactly one of todoId or projectId to list time entries.');
        }

        $input = array_filter([
            'ticketId' => $todoId !== null ? (string) (int) $todoId : null,
            'projectId' => $projectId !== null ? (string) (int) $projectId : null,
        ], fn ($value) => $value !== null);

        return $this->gateway->results(
            fn ($controller, $apiUser) => $controller->timesheets($input, $apiUser)
        );
    }
}
