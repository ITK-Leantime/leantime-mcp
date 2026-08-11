<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Adds a comment to a todo.
 */
class AddComment
{
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    /**
     * Comments on a todo as a given person. Note that team members are not notified by
     * email about comments added through this server.
     *
     * @param  int  $todoId  Todo to comment on.
     * @param  string  $text  The comment.
     * @param  string  $username  Email address of the comment's author.
     * @return array<string, mixed> The created comment.
     */
    public function __invoke(int $todoId, string $text, string $username): array
    {
        $this->gateway->assertCanWrite();

        return $this->gateway->single(
            fn ($controller, $apiUser) => $controller->createTicketComment(
                $todoId,
                ['text' => $text, 'username' => $username],
                $apiUser
            )
        );
    }
}
