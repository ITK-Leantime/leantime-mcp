<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;
use RuntimeException;

/**
 * Updates a todo's fields.
 */
#[IsDestructive(false)]
class UpdateTodo extends LeantimeTool
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
        return 'update_todo';
    }

    /**
     * The tool description shown to agents in tools/list.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Updates a todo. Only the fields you pass are changed — omitted fields keep their '
            . 'current values, so there is no need to read the todo first. To change progress, use '
            . 'set_todo_status instead.';
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
            ->description('Todo to update.')
            ->required()
            ->string('name')
            ->description('New title (max 255 characters).')
            ->string('description')
            ->description('New description.')
            ->string('dueDate')
            ->description('New due date as YYYY-MM-DD.')
            ->number('plannedHours')
            ->description('New estimate in hours.')
            ->number('remainingHours')
            ->description('Hours still remaining.')
            ->integer('milestoneId')
            ->description('Milestone to attach it to; must be in the same project.')
            ->raw('tags', [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'description' => 'Replaces the whole tag list.',
            ])
            ->string('assignee')
            ->description('Email address of the new assignee.');
    }

    /**
     * @param  array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    protected function run(array $arguments): array
    {
        $this->gateway->assertCanWrite();

        $todoId = (int) $this->requireArg($arguments, 'todoId');

        $input = array_filter([
            'name' => $arguments['name'] ?? null,
            'description' => $arguments['description'] ?? null,
            'dueDate' => $arguments['dueDate'] ?? null,
            'plannedHours' => isset($arguments['plannedHours']) ? (float) $arguments['plannedHours'] : null,
            'remainingHours' => isset($arguments['remainingHours']) ? (float) $arguments['remainingHours'] : null,
            'milestoneId' => isset($arguments['milestoneId']) ? (int) $arguments['milestoneId'] : null,
            'tags' => $arguments['tags'] ?? null,
            'assignee' => $arguments['assignee'] ?? null,
        ], fn ($value) => $value !== null);

        if ($input === []) {
            throw new RuntimeException('Pass at least one field to update.');
        }

        return $this->gateway->single(
            fn ($controller, $apiUser) => $controller->updateTicket($todoId, $input, $apiUser)
        );
    }
}
