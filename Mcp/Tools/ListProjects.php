<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Lists the projects the calling API key may access.
 */
#[IsReadOnly]
class ListProjects extends LeantimeTool
{
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    public function name(): string
    {
        return 'list_projects';
    }

    public function description(): string
    {
        return 'Lists the Leantime projects this API key can access, with their id, name and '
            .'state. Start here to find a project id for the other tools.';
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return list<mixed>
     */
    protected function run(array $arguments): array
    {
        return $this->gateway->results(
            fn ($controller, $apiUser) => $controller->projects($apiUser)
        );
    }
}
