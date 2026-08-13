<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Lists a todo's attachments.
 */
#[IsReadOnly]
class ListAttachments extends LeantimeTool
{
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    public function name(): string
    {
        return 'list_attachments';
    }

    public function description(): string
    {
        return 'Lists the files attached to a todo — filename, type, who uploaded it and when. '
            .'File contents are not available through this server; open the todo in Leantime to '
            .'download.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('todoId')
            ->description('Todo whose attachments to list.')
            ->required();
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return list<mixed>
     */
    protected function run(array $arguments): array
    {
        $todoId = (int) $this->requireArg($arguments, 'todoId');

        return $this->gateway->results(
            fn ($controller, $apiUser) => $controller->ticketFiles($todoId, $apiUser)
        );
    }
}
