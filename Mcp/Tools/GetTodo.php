<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Reads a single todo.
 */
#[IsReadOnly]
class GetTodo extends LeantimeTool
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
        return 'get_todo';
    }

    /**
     * The tool description shown to agents in tools/list.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Gets one todo by id, including its status, assignee, hours, tags and due date.';
    }

    /**
     * Declares the tool's input arguments.
     *
     * @return ToolInputSchema
     */
    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('todoId')
            ->description('Id of the todo to read.')
            ->required();
    }

    /**
     * @param  array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    protected function run(array $arguments): array
    {
        $todoId = (int) $this->requireArg($arguments, 'todoId');

        return $this->gateway->single(
            fn ($controller, $apiUser) => $controller->ticket($todoId, $apiUser)
        );
    }
}
