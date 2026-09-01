# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- **Requires Leantime 3.9.7 or newer.** Ported from `php-mcp/laravel` to Laravel's official
  `laravel/mcp`, which core switched to in 3.9.7 — on that version and later the old package is
  gone, and the plugin previously took the whole site down at boot rather than just failing to
  serve MCP.
- **Endpoint moved from `/mcp` to `/mcp/itk`.** Core now reserves `/mcp` for Leantime's own
  commercial `McpServer` plugin; on a shared path route order would decide which server
  answers. It stays *under* `/mcp/` because core's rate limiter only treats `/mcp`, `/mcp/*` and
  `/mcp?*` as MCP traffic — a sibling path would not be rate limited at all. Existing clients
  must be repointed.
- Tools are now classes extending `Laravel\Mcp\Server\Tool` with an explicit `schema()`, listed
  on `Mcp\LeantimeMcpServer`. The docblock-driven registry in `mcp.php` is gone, as is the
  hand-registered service provider and its seeded `mcp.*` config.
- Raised the `tools/list` page size to 50 so all 16 tools arrive on one page, and narrowed the
  advertised capabilities to `tools` only — the vendor default also announces resources and
  prompts, which this server does not implement.

### Added

- Plugin scaffold.
- Serves a Model Context Protocol server at `/mcp` over Streamable HTTP, so MCP clients
  connect by URL with no local process or per-machine API key.
- Authenticates `/mcp` with Databridge API keys, reusing its per-key operation and project
  grants so an agent only reaches what its key allows.
- `ping` and `whoami` tools for connectivity checks and grant discovery.
- `list_users` tool listing the people on reachable projects with their name, job title and
  projects, so an agent can turn "the designer" or a first name into the `username` that
  `list_todos`, `create_todo` and `log_time` require. Previously those tools needed a username
  with no way to discover one. Scoped to the key's grant, so it cannot enumerate the organisation.
- Project-management tools: read projects, progress, statuses, milestones, todos, comments,
  attachment metadata and time entries; create and update todos, set status, log time, and comment.
- Status is exchanged as `NEW`/`INPROGRESS`/`DONE` rather than a per-project id, so the same call
  works in any project regardless of how its statuses are named.
- Write tools require the key's `write` grant and read the record back after writing, so the
  result reflects what was stored rather than what was requested.
- `log_time` states that it is safe to retry, since only one entry can exist per person, todo,
  date and kind — a call whose result was never seen can be repeated without double-booking.

### Fixed

- Grant denials and Databridge errors now come back as readable tool errors instead of HTTP
  500s. `laravel/mcp` only catches validation and not-found exceptions itself, so a refused
  write would otherwise reach the client as a transport failure with no explanation.
- Missing required arguments are reported by name rather than raising an undefined-key error;
  the advertised schema marks arguments required but the library does not enforce it on calls.
- Clients requesting a newer MCP protocol revision than `laravel/mcp` knows are now negotiated
  down to the newest supported one instead of being refused. `mcp-remote` asks for `2025-11-25`,
  so **Claude Desktop could not connect at all** — it failed with `MCP error -32602: Unsupported
  protocol version`. Malformed or genuinely older revisions are still rejected.
- `tools/call` no longer fails with a protocol error when a client omits `arguments` entirely,
  which is legitimate for the tools that take no input (`ping`, `whoami`, `list_projects`).
- `list_todos` clamps `limit` to 1-200. A negative value made the query builder drop its `LIMIT`
  clause, so an agent passing `-1` to mean "no limit" would have pulled every assigned todo.
- `list_time_entries` rejects being given both `todoId` and `projectId`, matching the endpoint's
  exactly-one contract instead of only catching the neither-given case.
- Made the grant self-check fail properly. It used bare `assert()`, which PHP compiles out
  under the container's `zend.assertions=-1`, so it reported success even with the grant
  logic fully bypassed.
