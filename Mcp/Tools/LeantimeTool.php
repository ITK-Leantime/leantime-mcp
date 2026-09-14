<?php

namespace Leantime\Plugins\LeantimeMcp\Mcp\Tools;

use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolResult;
use Throwable;

/**
 * Base for this plugin's tools: turns thrown errors into tool errors.
 *
 * laravel/mcp's CallTool only catches ItemNotFoundException and ValidationException, so
 * anything else a tool throws escapes as an HTTP 500 with no JSON-RPC id — the client sees a
 * transport failure rather than a failed call. Our own failures are all RuntimeExceptions
 * (denied grant, Databridge error, missing argument), and they are the normal way a tool says
 * "no", so they must reach the agent as readable text.
 *
 * Subclasses implement run() and may throw freely; handle() adapts.
 */
abstract class LeantimeTool extends Tool
{
    /**
     * Run the tool, returning the payload to hand back as JSON.
     *
     * @param  array<string, mixed> $arguments
     * @return array<mixed>
     */
    abstract protected function run(array $arguments): array;

    /**
     * @param  array<string, mixed> $arguments
     * @return ToolResult The run() payload as JSON, or a thrown error as readable text.
     */
    public function handle(array $arguments): ToolResult
    {
        try {
            return ToolResult::json($this->run($arguments));
        } catch (Throwable $e) {
            /*
             * The message is the product here — Databridge's own wording ("Project not granted
             * for this API key.") is what lets an agent correct itself. Not logged: these are
             * expected outcomes, and the exception carries no context a log would add.
             */
            return ToolResult::error($e->getMessage());
        }
    }

    /**
     * Read a required argument, or fail with a message naming it.
     *
     * ToolInputSchema marks arguments required in the advertised schema, but v0.1.1 does not
     * enforce that server-side on tools/call — a client may still omit one, so read through
     * here rather than indexing $arguments directly.
     *
     * @param  array<string, mixed> $arguments
     * @return mixed The argument's value.
     */
    protected function requireArg(array $arguments, string $name): mixed
    {
        if (! array_key_exists($name, $arguments) || $arguments[$name] === null || $arguments[$name] === '') {
            throw new \RuntimeException('Missing required argument: ' . $name . '.');
        }

        return $arguments[$name];
    }
}
