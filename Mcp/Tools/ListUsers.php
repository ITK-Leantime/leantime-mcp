<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Lists the people on the projects this key can reach.
 */
#[IsReadOnly]
class ListUsers extends LeantimeTool
{
    /**
     * @param  DatabridgeGateway $gateway In-process bridge to Databridge's API controller.
     */
    public function __construct(private readonly DatabridgeGateway $gateway)
    {
    }

    /**
     * The tool name advertised in tools/list.
     *
     * @return string
     */
    public function name(): string
    {
        return 'list_users';
    }

    /**
     * The tool description shown to agents in tools/list.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Lists the people assigned to the projects this API key can access, with their '
            . 'username, name, job title and project ids. Use this to turn a person\'s name into '
            . 'the username that list_todos, log_time and create_todo require.';
    }

    /**
     * Declares the tool's input arguments.
     *
     * @return ToolInputSchema
     */
    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('projectId')
            ->description('Restrict to users on one project; omit for all accessible projects.');
    }

    /**
     * @param  array<string, mixed> $arguments
     * @return list<mixed>
     */
    protected function run(array $arguments): array
    {
        $projectId = $arguments['projectId'] ?? null;

        $input = $projectId !== null ? ['projectId' => (string) (int) $projectId] : [];

        return $this->gateway->results(
            fn ($controller, $apiUser) => $controller->users($input, $apiUser)
        );
    }
}
