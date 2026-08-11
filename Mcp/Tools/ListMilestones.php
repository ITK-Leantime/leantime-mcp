<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Lists milestones.
 */
class ListMilestones
{
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    /**
     * Lists milestones with their status and due date. Use these to group todos into phases
     * or to report on delivery dates.
     *
     * @param  ?int  $projectId  Restrict to one project; omit for all accessible projects.
     * @return list<mixed> Matching milestones.
     */
    public function __invoke(?int $projectId = null): array
    {
        $input = $projectId !== null ? ['projectId' => (string) $projectId] : [];

        return $this->gateway->results(
            fn ($controller, $apiUser) => $controller->milestones($input, $apiUser)
        );
    }
}
