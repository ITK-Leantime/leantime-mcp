# Leantime MCP Plugin

Serves a [Model Context Protocol](https://modelcontextprotocol.io) server from Leantime at
`/mcp`, so AI agents can read and write Leantime data through a small, explicit set of
tools.

Clients connect to a URL — there is no local process to install and no API key copied onto
each machine.

## Why not the built-in API?

Leantime's JSON-RPC API is reflective: `leantime.rpc.{Domain}.{Service}.{method}` reaches
any public method of any domain service, so one key can read every user record and delete
tickets. The `abilities` column on access tokens is stored but never enforced.

This plugin exposes only the tools registered in `mcp.php`, authenticated by
[Databridge](https://github.com/ITK-Leantime/leantime_databridge) API keys whose grants are
enforced server-side. There is deliberately no generic "call any service" tool.

## Requirements

- The **Databridge** plugin, installed and enabled — it provides API keys and the grant
  model this plugin authenticates against.
- Redis or another cache store (MCP sessions default to the cache driver).

## Installation

1. Place this folder in `app/Plugins/LeantimeMcp` (the folder name must match, as Leantime
   derives the namespace from it).
2. Install and enable:

   ```bash
   php bin/leantime plugin:install leantime/leantime-mcp
   php bin/leantime plugin:enable leantime/leantime-mcp
   php bin/leantime cache:clear
   ```

## Authentication

`/mcp` is exempt from Leantime's session auth and is instead protected by Databridge's
`ApiKeyAuth` middleware — **that middleware is the only thing standing in front of the
endpoint.** The key is passed in the `x-api-key` header.

Grants live in `<leantime>/config/databridge_auth.yaml`:

```yaml
users:
  - name: claude-agent
    key: "32+-random-chars"        # openssl rand -base64 32
    operations: [read, write]      # read-only keys cannot call write tools
    projects: [4, 7]               # or `all`
```

Opening a session requires the `read` grant. Tools that mutate data additionally require
`write`, and every tool checks the project against the key's grants — a key can never reach
a project it was not granted, regardless of the arguments it passes.

## Client setup

### Claude Code

```bash
claude mcp add --transport http leantime https://leantime.example.dk/mcp \
  --header "x-api-key: <your-key>"
```

### Claude Desktop

Settings → Connectors → add a custom connector pointing at
`https://leantime.example.dk/mcp` with the same `x-api-key` header.

Any other MCP client that supports Streamable HTTP works the same way.

## Tools

| Tool | Grant | Description |
|---|---|---|
| `ping` | read | Connectivity check; touches no data |
| `whoami` | read | Reports the calling key's operations and projects |

## Development

Register tools in `mcp.php`; each is a class with an `__invoke()` method whose docblock
supplies the description and input schema.

Tools must resolve services **lazily** (inside the handler, via `app()->make()` or
constructor injection at request time). Plugins load in database order with no dependency
graph, so touching another plugin's classes during registration is not safe.

Run the grant self-check (exits non-zero on failure, so it works in CI):

```bash
php app/Plugins/LeantimeMcp/tests/grants_test.php
```

Probe the endpoint directly:

```bash
curl -s -X POST https://leantime.example.dk/mcp \
  -H "x-api-key: <your-key>" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json, text/event-stream" \
  -d '{"jsonrpc":"2.0","method":"initialize","params":{"protocolVersion":"2025-03-26","capabilities":{},"clientInfo":{"name":"probe","version":"1"}},"id":1}'
```

The response carries an `mcp-session-id` header; pass it back as `Mcp-Session-Id` on
subsequent `tools/list` and `tools/call` requests.
