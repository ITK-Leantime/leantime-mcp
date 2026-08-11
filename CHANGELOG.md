# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Plugin scaffold.
- Serves a Model Context Protocol server at `/mcp` over Streamable HTTP, so MCP clients
  connect by URL with no local process or per-machine API key.
- Authenticates `/mcp` with Databridge API keys, reusing its per-key operation and project
  grants so an agent only reaches what its key allows.
- `ping` and `whoami` tools for connectivity checks and grant discovery.

### Fixed

- Turned filesystem tool discovery off with the key the provider actually reads
  (`discovery.auto_discover`), removing a registry rebuild and cache write on every request.
- Kept vendor MCP config defaults intact; the previous setup silently dropped sibling keys
  such as `save_to_cache` and `cors_origin` through a shallow config merge.
- Made the grant self-check fail properly. It used bare `assert()`, which PHP compiles out
  under the container's `zend.assertions=-1`, so it reported success even with the grant
  logic fully bypassed.
- Stopped serving `Access-Control-Allow-Origin: *` on `/mcp`.
