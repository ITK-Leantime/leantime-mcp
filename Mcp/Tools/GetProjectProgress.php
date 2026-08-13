<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Reports a project's completion progress.
 */
#[IsReadOnly]
class GetProjectProgress extends LeantimeTool
{
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    public function name(): string
    {
        return 'get_project_progress';
    }

    public function description(): string
    {
        return 'Gets how far along a project is: percent complete, plus estimated and planned '
            .'completion dates. Use this to report status on a project as a whole.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('projectId')
            ->description('Project to report on; must be one this key may access.')
            ->required();
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    protected function run(array $arguments): array
    {
        $projectId = (int) $this->requireArg($arguments, 'projectId');

        return $this->gateway->single(
            fn ($controller, $apiUser) => $controller->projectProgress($projectId, $apiUser)
        );
    }
}
