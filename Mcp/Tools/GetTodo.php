<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Reads a single todo.
 */
class GetTodo
{
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    /**
     * Gets one todo by id, including its status, assignee, hours, tags and due date.
     *
     * @param  int  $todoId  Id of the todo to read.
     * @return array<string, mixed> The todo.
     */
    public function __invoke(int $todoId): array
    {
        return $this->gateway->single(
            fn ($controller, $apiUser) => $controller->ticket($todoId, $apiUser)
        );
    }
}
