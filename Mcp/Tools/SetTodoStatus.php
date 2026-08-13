<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Moves a todo between not-started, in-progress and done.
 */
class SetTodoStatus
{
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    /**
     * Sets a todo's progress. Takes a type rather than a status name because each project
     * names its statuses differently — this works in any project.
     *
     * @param  int  $todoId  Todo to move.
     * @param  string  $status  NEW (not started), INPROGRESS, or DONE.
     * @return array<string, mixed> The updated todo, read back after writing so the reported
     *                              status reflects what was actually stored.
     */
    public function __invoke(int $todoId, string $status): array
    {
        $this->gateway->assertCanWrite();

        return $this->gateway->single(
            fn ($controller, $apiUser) => $controller->updateTicket($todoId, ['status' => $status], $apiUser)
        );
    }
}
