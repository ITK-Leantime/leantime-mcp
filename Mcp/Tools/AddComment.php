<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Adds a comment to a todo.
 */
#[IsDestructive(false)]
class AddComment extends LeantimeTool
{
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    public function name(): string
    {
        return 'add_comment';
    }

    public function description(): string
    {
        return 'Comments on a todo as a given person. Note that team members are not notified by '
            .'email about comments added through this server.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('todoId')
            ->description('Todo to comment on.')
            ->required()
            ->string('text')
            ->description('The comment.')
            ->required()
            ->string('username')
            ->description('Email address of the comment\'s author.')
            ->required();
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    protected function run(array $arguments): array
    {
        $this->gateway->assertCanWrite();

        $todoId = (int) $this->requireArg($arguments, 'todoId');
        $text = $this->requireArg($arguments, 'text');
        $username = $this->requireArg($arguments, 'username');

        return $this->gateway->single(
            fn ($controller, $apiUser) => $controller->createTicketComment(
                $todoId,
                ['text' => $text, 'username' => $username],
                $apiUser
            )
        );
    }
}
