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

Desktop's `claude_desktop_config.json` accepts **stdio servers only** — its schema is
`{command, args?, env?}` with no `url` field, so a remote server cannot be entered directly.
Adding one is silently skipped with "Some MCP servers could not be loaded".

Bridge to it with [`mcp-remote`](https://www.npmjs.com/package/mcp-remote) instead. Edit
`~/Library/Application Support/Claude/claude_desktop_config.json` (macOS) or
`%APPDATA%\Claude\claude_desktop_config.json` (Windows):

```json
{
  "mcpServers": {
    "leantime": {
      "command": "npx",
      "args": [
        "-y", "mcp-remote", "https://leantime.example.dk/mcp",
        "--header", "x-api-key:your-key"
      ]
    }
  }
}
```

Desktop reads this file once at startup, so quit it fully (`Cmd+Q`, not just closing the
window) and reopen. The key is stored in plaintext here; scope it to the projects and
operations it actually needs.

Any other MCP client that speaks Streamable HTTP connects like Claude Code, by URL. Clients
that only support stdio need the `mcp-remote` bridge above.

### Self-signed certificates

A development instance behind a private CA (for example a local Traefik cert) is rejected by
Node's own trust store even when the certificate is trusted by the OS, failing with
`DEPTH_ZERO_SELF_SIGNED_CERT`.

Point Node at the CA:

- **Claude Code** — export it in your shell before launching; a `settings.json` `env` block
  does *not* work, because that applies to spawned subprocesses rather than to Claude Code's
  own TLS connections:

  ```bash
  export NODE_EXTRA_CA_CERTS="/path/to/ca.crt"
  ```

- **Claude Desktop** — add it to the bridge's `env`, which does apply since `mcp-remote` is a
  spawned subprocess. Desktop is launched from the GUI and never reads your shell profile, so
  the shell export above has no effect on it:

  ```json
  "env": { "NODE_EXTRA_CA_CERTS": "/path/to/ca.crt" }
  ```

Neither is needed against a deployment with a publicly trusted certificate.

## Tools

Read tools are marked `readOnlyHint` so clients can treat writes with more care. Every tool is
scoped to the calling key's granted projects, and there is no delete tool and no generic
"call any service" tool — those are unreachable by construction, not by a warning.

| Tool | Grant | Description |
| --- | --- | --- |
| `ping` | read | Connectivity check; touches no data |
| `whoami` | read | Reports the calling key's operations and projects |
| `list_projects` | read | Projects this key can access |
| `get_project_progress` | read | Percent complete and completion dates |
| `list_statuses` | read | A project's status labels and their types |
| `list_milestones` | read | Milestones with status and due date |
| `list_todos` | read | A person's todos, filterable by status and due date |
| `get_todo` | read | One todo in full |
| `list_comments` | read | A todo's discussion |
| `list_attachments` | read | Attachment metadata (never file contents) |
| `list_time_entries` | read | Logged time for a todo or project |
| `create_todo` | write | Create a todo and assign it |
| `update_todo` | write | Change selected fields; omitted fields are left alone |
| `set_todo_status` | write | Move a todo to NEW, INPROGRESS or DONE |
| `log_time` | write | Log hours against a todo |
| `add_comment` | write | Comment on a todo |

Status is exchanged as `NEW`/`INPROGRESS`/`DONE` rather than a number, because status ids are
configured per project and can be renamed or translated. Use `list_statuses` to show a person
the project's own wording.

Write tools read the todo back after writing, so the status they report is what was actually
stored rather than what was requested.

> **No notifications are sent.** Writes go through Databridge, which updates Leantime directly
> rather than through core's services. Team members get no email when an agent creates a todo,
> changes a status, logs time, or comments.

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
