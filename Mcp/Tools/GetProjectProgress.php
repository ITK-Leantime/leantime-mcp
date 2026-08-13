<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Reports a project's completion progress.
 */
class GetProjectProgress
{
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    /**
     * Gets how far along a project is: percent complete, plus estimated and planned
     * completion dates. Use this to report status on a project as a whole.
     *
     * @param  int  $projectId  Project to report on; must be one this key may access.
     * @return array<string, mixed> Percent complete and completion dates.
     */
    public function __invoke(int $projectId): array
    {
        return $this->gateway->single(
            fn ($controller, $apiUser) => $controller->projectProgress($projectId, $apiUser)
        );
    }
}
