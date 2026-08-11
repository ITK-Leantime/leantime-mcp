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
