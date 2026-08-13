<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp;

use Generator;
use Laravel\Mcp\Server\Methods\CallTool;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Transport\JsonRpcRequest;
use Laravel\Mcp\Server\Transport\JsonRpcResponse;

/**
 * tools/call handler that tolerates a request with no 'arguments' key.
 *
 * laravel/mcp v0.1.1 reads $request->params['arguments'] unconditionally, so a client that
 * omits it — legitimate for a tool that takes no input, and what several clients send for
 * ping, whoami and list_projects — gets a JSON-RPC protocol error ('Undefined array key
 * "arguments"') rather than a result. LeantimeTool cannot catch that: the failure happens in
 * CallTool before any tool is reached.
 *
 * Defaults the key and hands off to the vendor implementation, so pagination, streaming and
 * error handling stay exactly as upstream. Delete once the vendor defaults it themselves.
 */
class CallToolWithOptionalArguments extends CallTool
{
    /**
     * @return JsonRpcResponse|Generator<mixed>
     */
    public function handle(JsonRpcRequest $request, ServerContext $context)
    {
        if (! array_key_exists('arguments', $request->params)) {
            $request->params['arguments'] = [];
        }

        return parent::handle($request, $context);
    }
}
