<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Leantime\Plugins\LeantimeMcp\Mcp\ApiUserContext;

/**
 * Reports the grants held by the calling API key.
 *
 * Lets an agent discover what it is allowed to do before attempting it, and proves the auth
 * bridge resolves the Databridge ApiUser inside a tool call.
 */
class WhoAmI
{
    public function __construct(private readonly ApiUserContext $context) {}

    /**
     * Shows which operations and projects the current API key may access.
     *
     * @return array{name: string, operations: list<string>, projects: string} The key's name,
     *                                                                         granted operations, and accessible project IDs ('all' when unrestricted).
     */
    public function __invoke(): array
    {
        $user = $this->context->user();

        return [
            'name' => $user->name,
            'operations' => array_map(fn ($operation) => $operation->value, $user->operations),
            // null projects means the 'all' sentinel; render it rather than leaking null.
            'projects' => $user->projects === null ? 'all' : implode(',', $user->projects),
        ];
    }
}
