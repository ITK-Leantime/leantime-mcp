<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Leantime\Plugins\LeantimeMcp\Mcp\DatabridgeGateway;

/**
 * Lists a person's todos, with optional date and status filtering.
 */
class ListTodos
{
    public function __construct(private readonly DatabridgeGateway $gateway) {}

    /**
     * Lists todos assigned to a person, newest ids last. Results only ever include projects
     * this API key may access.
     *
     * @param  string  $username  Email address of the person whose todos to list.
     * @param  ?string  $status  Filter by progress: NEW, INPROGRESS or DONE.
     * @param  ?string  $dueFrom  Only todos due on or after this date (YYYY-MM-DD).
     * @param  ?string  $dueTo  Only todos due on or before this date (YYYY-MM-DD).
     * @param  int  $limit  Maximum todos to return (default 50).
     * @param  int  $sinceId  Return todos with an id at or above this, for paging.
     * @return list<mixed> Matching todos.
     */
    public function __invoke(
        string $username,
        ?string $status = null,
        ?string $dueFrom = null,
        ?string $dueTo = null,
        int $limit = 50,
        int $sinceId = 0,
    ): array {
        $input = array_filter([
            'username' => $username,
            'status' => $status,
            'dateFrom' => $dueFrom,
            'dateTo' => $dueTo,
            'limit' => (string) $limit,
            'sinceId' => (string) $sinceId,
        ], fn ($value) => $value !== null);

        return $this->gateway->results(
            fn ($controller, $apiUser) => $controller->tickets($input, $apiUser)
        );
    }
}
