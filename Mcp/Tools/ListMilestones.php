<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Lists milestones.
 */
#[IsReadOnly]
class ListMilestones extends LeantimeTool
{
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    public function name(): string
    {
        return 'list_milestones';
    }

    public function description(): string
    {
        return 'Lists milestones with their status and due date. Use these to group todos into '
            .'phases or to report on delivery dates.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('projectId')
            ->description('Restrict to one project; omit for all accessible projects.');
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return list<mixed>
     */
    protected function run(array $arguments): array
    {
        $projectId = $arguments['projectId'] ?? null;

        $input = $projectId !== null ? ['projectId' => (string) (int) $projectId] : [];

        return $this->gateway->results(
            fn ($controller, $apiUser) => $controller->milestones($input, $apiUser)
        );
    }
}
