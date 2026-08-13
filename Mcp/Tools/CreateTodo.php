<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Creates a todo.
 */
class CreateTodo
{
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    /**
     * Creates a todo in a project and assigns it to someone. Returns the created todo,
     * including the id needed to update it later.
     *
     * @param  int  $projectId  Project to create the todo in.
     * @param  string  $name  Short title for the todo (max 255 characters).
     * @param  string  $username  Email address of the person to assign it to.
     * @param  ?string  $description  Longer description of the work.
     * @param  ?string  $dueDate  Due date as YYYY-MM-DD.
     * @param  ?float  $plannedHours  Estimated hours.
     * @param  ?int  $milestoneId  Milestone to attach it to; must be in the same project.
     * @param  ?array  $tags  Tags to set on the todo.
     * @return array<string, mixed> The created todo.
     */
    public function __invoke(
        int $projectId,
        string $name,
        string $username,
        ?string $description = null,
        ?string $dueDate = null,
        ?float $plannedHours = null,
        ?int $milestoneId = null,
        ?array $tags = null,
    ): array {
        $this->gateway->assertCanWrite();

        $input = array_filter([
            'projectId' => $projectId,
            'name' => $name,
            'username' => $username,
            'description' => $description,
            'dueDate' => $dueDate,
            'plannedHours' => $plannedHours,
            'milestoneId' => $milestoneId,
            'tags' => $tags,
        ], fn ($value) => $value !== null);

        return $this->gateway->single(
            fn ($controller, $apiUser) => $controller->createTicket($input, $apiUser)
        );
    }
}
