<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Moves a todo between not-started, in-progress and done.
 */
#[IsDestructive(false)]
class SetTodoStatus extends LeantimeTool
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
        return 'set_todo_status';
    }

    /**
     * The tool description shown to agents in tools/list.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Sets a todo\'s progress. Takes a type rather than a status name because each '
            . 'project names its statuses differently — this works in any project.';
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
            ->description('Todo to move.')
            ->required()
            ->string('status')
            ->description('NEW (not started), INPROGRESS, or DONE.')
            ->required();
    }

    /**
     * @param  array<string, mixed> $arguments
     * @return array<string, mixed> The updated todo, read back after writing so the reported
     *                              status reflects what was actually stored.
     */
    protected function run(array $arguments): array
    {
        $this->gateway->assertCanWrite();

        $todoId = (int) $this->requireArg($arguments, 'todoId');
        $status = $this->requireArg($arguments, 'status');

        return $this->gateway->single(
            fn ($controller, $apiUser) => $controller->updateTicket($todoId, ['status' => $status], $apiUser)
        );
    }
}
