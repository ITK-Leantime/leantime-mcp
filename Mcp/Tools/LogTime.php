<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Logs time against a todo.
 */
class LogTime
{
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    /**
     * Logs hours worked on a todo for a given person and date.
     *
     * @param  int  $todoId  Todo the work belongs to.
     * @param  float  $hours  Hours worked.
     * @param  string  $workDate  Date the work happened, as YYYY-MM-DD.
     * @param  string  $username  Email address of the person who did the work.
     * @param  ?string  $description  What was done.
     * @param  ?string  $kind  Timesheet kind; omit for the project's default.
     * @return array<string, mixed> The created time entry.
     */
    public function __invoke(
        int $todoId,
        float $hours,
        string $workDate,
        string $username,
        ?string $description = null,
        ?string $kind = null,
    ): array {
        $this->gateway->assertCanWrite();

        $input = array_filter([
            'ticketId' => $todoId,
            'hours' => $hours,
            'workDate' => $workDate,
            'username' => $username,
            'description' => $description,
            'kind' => $kind,
        ], fn ($value) => $value !== null);

        return $this->gateway->single(
            fn ($controller, $apiUser) => $controller->createTimesheet($input, $apiUser)
        );
    }
}
