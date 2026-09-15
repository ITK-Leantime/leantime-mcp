<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Lists a todo's comments.
 */
#[IsReadOnly]
class ListComments extends LeantimeTool
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
        return 'list_comments';
    }

    /**
     * The tool description shown to agents in tools/list.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Lists the comments on a todo, oldest first, so you can follow the discussion.';
    }

    /**
     * Declares the tool's input arguments.
     *
     * @return ToolInputSchema
     */
    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('todoId')
            ->description('Todo whose comments to read.')
            ->required();
    }

    /**
     * @param  array<string, mixed> $arguments
     * @return list<mixed>
     */
    protected function run(array $arguments): array
    {
        $todoId = (int) $this->requireArg($arguments, 'todoId');

        return $this->gateway->results(
            fn ($controller, $apiUser) => $controller->ticketComments($todoId, $apiUser)
        );
    }
}
