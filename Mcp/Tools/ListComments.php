<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Lists a todo's comments.
 */
class ListComments
{
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    /**
     * Lists the comments on a todo, oldest first, so you can follow the discussion.
     *
     * @param  int  $todoId  Todo whose comments to read.
     * @return list<mixed> Comments with author and date.
     */
    public function __invoke(int $todoId): array
    {
        return $this->gateway->results(
            fn ($controller, $apiUser) => $controller->ticketComments($todoId, $apiUser)
        );
    }
}
