<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp;

use Leantime\Plugins\Databridge\Middleware\ApiKeyAuth;
use Leantime\Plugins\Databridge\Model\ApiUser;
use Leantime\Plugins\Databridge\Model\Operation;
use RuntimeException;

/**
 * Resolves the API user that ApiKeyAuth authenticated for the current request.
 *
 * Tools MUST derive project scope from here rather than from their own arguments: an
 * argument is caller-supplied, so trusting it would let any key name any project. The
 * grants on this user are the only authority on what is reachable.
 *
 * Databridge is referenced lazily — this class is only ever constructed while handling a
 * request, never during plugin registration, so it cannot depend on plugin load order.
 */
class ApiUserContext
{
    /**
     * The authenticated API user for this request.
     *
     * @return ApiUser
     *
     * @throws RuntimeException If the request never passed through ApiKeyAuth, which would
     *                          mean the /mcp middleware is misconfigured. Fail loudly rather
     *                          than defaulting to an unscoped user.
     */
    public function user(): ApiUser
    {
        $user = request()->attributes->get(ApiKeyAuth::REQUEST_ATTRIBUTE);

        if (! $user instanceof ApiUser) {
            throw new RuntimeException(
                'MCP tool reached without an authenticated ApiUser — ApiKeyAuth middleware missing from the /mcp route group.'
            );
        }

        return $user;
    }

    /**
     * Assert the API key holds the write grant, for tools that mutate data.
     *
     * The /mcp route group only requires ':read', so every mutating tool must call this.
     *
     * @return void
     *
     * @throws RuntimeException If the key lacks the write grant.
     */
    public function assertCanWrite(): void
    {
        if (! $this->user()->can(Operation::Write)) {
            throw new RuntimeException('This API key does not have the write grant.');
        }
    }

    /**
     * Assert the API key may access the given project.
     *
     * @return void
     *
     * @throws RuntimeException If the project is outside the key's grants.
     */
    public function assertCanAccessProject(int $projectId): void
    {
        if (! $this->user()->canAccessProject($projectId)) {
            throw new RuntimeException('This API key does not have access to project ' . $projectId . '.');
        }
    }
}
