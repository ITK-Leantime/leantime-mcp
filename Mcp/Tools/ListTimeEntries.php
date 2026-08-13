<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;
use RuntimeException;

/**
 * Lists logged time entries.
 */
class ListTimeEntries
{
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    /**
     * Lists time logged against a todo or across a project, with hours and work dates.
     *
     * @param  ?int  $todoId  Restrict to one todo.
     * @param  ?int  $projectId  Restrict to one project.
     * @return list<mixed> Matching time entries.
     */
    public function __invoke(?int $todoId = null, ?int $projectId = null): array
    {
        if ($todoId === null && $projectId === null) {
            throw new RuntimeException('Pass either todoId or projectId to list time entries.');
        }

        $input = array_filter([
            'ticketId' => $todoId !== null ? (string) $todoId : null,
            'projectId' => $projectId !== null ? (string) $projectId : null,
        ], fn ($value) => $value !== null);

        return $this->gateway->results(
            fn ($controller, $apiUser) => $controller->timesheets($input, $apiUser)
        );
    }
}
