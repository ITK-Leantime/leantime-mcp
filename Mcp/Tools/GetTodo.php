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
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    public function name(): string
    {
        return 'get_todo';
    }

    public function description(): string
    {
        return 'Gets one todo by id, including its status, assignee, hours, tags and due date.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('todoId')
            ->description('Id of the todo to read.')
            ->required();
    }

    /**
     * @param  array<string, mixed>  $arguments
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
