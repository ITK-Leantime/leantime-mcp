<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp;

use Leantime\Plugins\Databridge\Controllers\Api;
use Leantime\Plugins\Databridge\Model\Operation;
use Leantime\Plugins\Databridge\Services\Databridge;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Calls Databridge's API controller in-process on behalf of an MCP tool.
 *
 * Tools go through the controller rather than the service so they inherit its input
 * validation, grant checks and error messages verbatim — the alternative is a second,
 * drifting copy of that logic. It is called directly rather than over HTTP: an outbound
 * request to our own host would need the key round-tripped and would double the work per
 * tool call for no benefit.
 *
 * Databridge is resolved lazily here, never at plugin registration: plugins load in
 * database order with no dependency graph, so its classes cannot be touched until a request
 * is being handled.
 */
class DatabridgeGateway
{
    public function __construct(private readonly ApiUserContext $context) {}

    /**
     * Whether the calling key holds the write grant.
     *
     * Read tools need no check — the /mcp route group already requires ':read'.
     */
    public function assertCanWrite(): void
    {
        $this->context->assertCanWrite();
    }

    /**
     * Invoke a controller method and return its decoded payload.
     *
     * $call receives the controller and the authenticated ApiUser. Anything other than a
     * 2xx is surfaced as an exception carrying the controller's own message, so the agent
     * sees "Project not granted for this API key." rather than a bare failure.
     *
     * @param  callable(Api, \Leantime\Plugins\Databridge\Model\ApiUser): JsonResponse  $call
     * @return array<string, mixed> The decoded response body.
     */
    public function call(callable $call): array
    {
        if (! class_exists(Api::class)) {
            throw new RuntimeException(
                'The Databridge plugin is required by leantime-mcp but is not installed or enabled.'
            );
        }

        $controller = app()->make(Api::class);
        $controller->init(app()->make(Databridge::class));

        $response = $call($controller, $this->context->user());

        $payload = json_decode((string) $response->getContent(), true);

        if (! is_array($payload)) {
            throw new RuntimeException('Databridge returned a non-JSON response.');
        }

        $status = $response->getStatusCode();

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException($payload['error'] ?? "Databridge request failed with status {$status}.");
        }

        return $payload;
    }

    /**
     * Invoke a controller method and return just the `results` list.
     *
     * Every Databridge endpoint wraps its payload in ResponseData as
     * {parameters, resultsCount, results}. Tools return the rows alone: the echoed
     * parameters are what the agent just sent, so repeating them back only spends tokens.
     *
     * @param  callable(Api, \Leantime\Plugins\Databridge\Model\ApiUser): JsonResponse  $call
     * @return list<mixed>
     */
    public function results(callable $call): array
    {
        return $this->call($call)['results'] ?? [];
    }

    /**
     * Invoke a controller method expected to return exactly one row, and return that row.
     *
     * @param  callable(Api, \Leantime\Plugins\Databridge\Model\ApiUser): JsonResponse  $call
     * @return array<string, mixed>
     */
    public function single(callable $call): array
    {
        $results = $this->results($call);

        if ($results === []) {
            throw new RuntimeException('Databridge returned no result.');
        }

        return (array) $results[0];
    }

    /**
     * The operation grants held by the calling key, for tools that report capability.
     *
     * @return list<Operation>
     */
    public function grantedOperations(): array
    {
        return $this->context->user()->operations;
    }
}
