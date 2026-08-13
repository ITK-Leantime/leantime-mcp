<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;
use RuntimeException;

/**
 * Updates a todo's fields.
 */
class UpdateTodo
{
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    /**
     * Updates a todo. Only the fields you pass are changed — omitted fields keep their
     * current values, so there is no need to read the todo first. To change progress, use
     * set_todo_status instead.
     *
     * @param  int  $todoId  Todo to update.
     * @param  ?string  $name  New title (max 255 characters).
     * @param  ?string  $description  New description.
     * @param  ?string  $dueDate  New due date as YYYY-MM-DD.
     * @param  ?float  $plannedHours  New estimate in hours.
     * @param  ?float  $remainingHours  Hours still remaining.
     * @param  ?int  $milestoneId  Milestone to attach it to; must be in the same project.
     * @param  ?array  $tags  Replaces the whole tag list.
     * @param  ?string  $assignee  Email address of the new assignee.
     * @return array<string, mixed> The updated todo, read back after writing.
     */
    public function __invoke(
        int $todoId,
        ?string $name = null,
        ?string $description = null,
        ?string $dueDate = null,
        ?float $plannedHours = null,
        ?float $remainingHours = null,
        ?int $milestoneId = null,
        ?array $tags = null,
        ?string $assignee = null,
    ): array {
        $this->gateway->assertCanWrite();

        $input = array_filter([
            'name' => $name,
            'description' => $description,
            'dueDate' => $dueDate,
            'plannedHours' => $plannedHours,
            'remainingHours' => $remainingHours,
            'milestoneId' => $milestoneId,
            'tags' => $tags,
            'assignee' => $assignee,
        ], fn ($value) => $value !== null);

        if ($input === []) {
            throw new RuntimeException('Pass at least one field to update.');
        }

        return $this->gateway->single(
            fn ($controller, $apiUser) => $controller->updateTicket($todoId, $input, $apiUser)
        );
    }
}
