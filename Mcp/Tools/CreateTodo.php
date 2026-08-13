<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Creates a todo.
 */
#[IsDestructive(false)]
class CreateTodo extends LeantimeTool
{
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    public function name(): string
    {
        return 'create_todo';
    }

    public function description(): string
    {
        return 'Creates a todo in a project and assigns it to someone. Returns the created todo, '
            .'including the id needed to update it later.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('projectId')
            ->description('Project to create the todo in.')
            ->required()
            ->string('name')
            ->description('Short title for the todo (max 255 characters).')
            ->required()
            ->string('username')
            ->description('Email address of the person to assign it to.')
            ->required()
            ->string('description')
            ->description('Longer description of the work.')
            ->string('dueDate')
            ->description('Due date as YYYY-MM-DD.')
            ->number('plannedHours')
            ->description('Estimated hours.')
            ->integer('milestoneId')
            ->description('Milestone to attach it to; must be in the same project.')
            // v0.1.1 has no array builder, so declare the list shape directly.
            ->raw('tags', [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'description' => 'Tags to set on the todo.',
            ]);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    protected function run(array $arguments): array
    {
        $this->gateway->assertCanWrite();

        $input = array_filter([
            'projectId' => (int) $this->requireArg($arguments, 'projectId'),
            'name' => $this->requireArg($arguments, 'name'),
            'username' => $this->requireArg($arguments, 'username'),
            'description' => $arguments['description'] ?? null,
            'dueDate' => $arguments['dueDate'] ?? null,
            'plannedHours' => isset($arguments['plannedHours']) ? (float) $arguments['plannedHours'] : null,
            'milestoneId' => isset($arguments['milestoneId']) ? (int) $arguments['milestoneId'] : null,
            'tags' => $arguments['tags'] ?? null,
        ], fn ($value) => $value !== null);

        return $this->gateway->single(
            fn ($controller, $apiUser) => $controller->createTicket($input, $apiUser)
        );
    }
}
