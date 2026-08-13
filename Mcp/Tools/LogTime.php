<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Logs time against a todo.
 *
 * IsIdempotent: only one entry can exist per person, todo, date and kind, so a retry cannot
 * book the same work twice.
 */
#[IsDestructive(false)]
#[IsIdempotent]
class LogTime extends LeantimeTool
{
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    public function name(): string
    {
        return 'log_time';
    }

    public function description(): string
    {
        return 'Logs hours worked on a todo for a given person and date. Safe to retry: only one '
            .'entry can exist per person, todo, date and kind, so repeating a call whose result '
            .'you never saw cannot book the same work twice — it reports that the entry already '
            .'exists instead. To log more time for the same day, read the entry with '
            .'list_time_entries and add to its hours rather than logging a second one.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('todoId')
            ->description('Todo the work belongs to.')
            ->required()
            ->number('hours')
            ->description('Hours worked.')
            ->required()
            ->string('workDate')
            ->description('Date the work happened, as YYYY-MM-DD.')
            ->required()
            ->string('username')
            ->description('Email address of the person who did the work.')
            ->required()
            ->string('description')
            ->description('What was done.')
            ->string('kind')
            ->description('Timesheet kind; omit for the project\'s default.');
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    protected function run(array $arguments): array
    {
        $this->gateway->assertCanWrite();

        $input = array_filter([
            'ticketId' => (int) $this->requireArg($arguments, 'todoId'),
            'hours' => (float) $this->requireArg($arguments, 'hours'),
            'workDate' => $this->requireArg($arguments, 'workDate'),
            'username' => $this->requireArg($arguments, 'username'),
            'description' => $arguments['description'] ?? null,
            'kind' => $arguments['kind'] ?? null,
        ], fn ($value) => $value !== null);

        return $this->gateway->single(
            fn ($controller, $apiUser) => $controller->createTimesheet($input, $apiUser)
        );
    }
}
