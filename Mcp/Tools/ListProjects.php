<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Lists the projects the calling API key may access.
 */
class ListProjects
{
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    /**
     * Lists the Leantime projects this API key can access. Start here to find a project id
     * for the other tools.
     *
     * @return list<mixed> Projects with their id, name and state.
     */
    public function __invoke(): array
    {
        return $this->gateway->results(
            fn ($controller, $apiUser) => $controller->projects($apiUser)
        );
    }
}
