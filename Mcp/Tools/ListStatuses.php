<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Lists a project's status labels.
 */
class ListStatuses
{
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    /**
     * Lists the statuses configured for a project, each with its type (NEW, INPROGRESS or
     * DONE). Status names are per-project and may be renamed or translated, so read them
     * here before describing a todo's state to a person.
     *
     * @param  int  $projectId  Project whose statuses to list.
     * @return list<mixed> Statuses with their label and type.
     */
    public function __invoke(int $projectId): array
    {
        return $this->gateway->results(
            fn ($controller, $apiUser) => $controller->projectStatuses($projectId, $apiUser)
        );
    }
}
