<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Lists a project's status labels.
 */
#[IsReadOnly]
class ListStatuses extends LeantimeTool
{
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
        return 'list_statuses';
    }

    /**
     * The tool description shown to agents in tools/list.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Lists the statuses configured for a project, each with its type (NEW, INPROGRESS '
            . 'or DONE). Status names are per-project and may be renamed or translated, so read '
            . 'them here before describing a todo\'s state to a person.';
    }

    /**
     * Declares the tool's input arguments.
     *
     * @return ToolInputSchema
     */
    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('projectId')
            ->description('Project whose statuses to list.')
            ->required();
    }

    /**
     * @param  array<string, mixed> $arguments
     * @return list<mixed>
     */
    protected function run(array $arguments): array
    {
        $projectId = (int) $this->requireArg($arguments, 'projectId');

        return $this->gateway->results(
            fn ($controller, $apiUser) => $controller->projectStatuses($projectId, $apiUser)
        );
    }
}
