<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Leantime\Plugins\LeantimeMcp\Mcp\ApiUserContext;

/**
 * Reports the grants held by the calling API key.
 *
 * Lets an agent discover what it is allowed to do before attempting it, and proves the auth
 * bridge resolves the Databridge ApiUser inside a tool call.
 */
#[IsReadOnly]
class WhoAmI extends LeantimeTool
{
    /**
     * @param  ApiUserContext $context The calling key's grants, as authenticated by ApiKeyAuth.
     */
    public function __construct(private readonly ApiUserContext $context)
    {
    }

    /**
     * The tool name advertised in tools/list.
     *
     * @return string
     */
    public function name(): string
    {
        return 'whoami';
    }

    /**
     * The tool description shown to agents in tools/list.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Shows which operations and projects the current API key may access.';
    }

    /**
     * @param  array<string, mixed> $arguments
     * @return array{name: string, operations: list<string>, projects: string}
     */
    protected function run(array $arguments): array
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
