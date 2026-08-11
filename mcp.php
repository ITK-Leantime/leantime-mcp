<?php

use Leantime\Plugins\LeantimeMcp\Mcp\Tools\AddComment;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\CreateTodo;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\GetProjectProgress;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\GetTodo;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\ListAttachments;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\ListComments;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\ListMilestones;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\ListProjects;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\ListStatuses;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\ListTimeEntries;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\ListTodos;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\LogTime;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\Ping;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\SetTodoStatus;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\UpdateTodo;
use Leantime\Plugins\LeantimeMcp\Mcp\Tools\WhoAmI;
use PhpMcp\Laravel\Facades\Mcp;
use PhpMcp\Schema\ToolAnnotations;

/*
 * MCP tool registry. Loaded by McpServiceProvider::loadMcpDefinitions() via the
 * mcp.discovery.definitions_file config set in register.php.
 *
 * Only what is registered here is callable — there is deliberately no generic
 * "call any service" tool, which would hand back the full API surface this plugin exists to
 * narrow. There is also no tool that deletes anything: deletion is unreachable by
 * construction rather than guarded by a warning in a description.
 *
 * readOnlyHint tells clients which tools cannot change data, so they can treat the writes
 * with more care. It is a hint for the client, not a boundary — the write grant on the API
 * key is what actually stops a read-only key from mutating anything.
 */

$readOnly = ToolAnnotations::make(readOnlyHint: true);

// Diagnostics.
Mcp::tool('ping', Ping::class)->annotations($readOnly);
Mcp::tool('whoami', WhoAmI::class)->annotations($readOnly);

// Reading projects.
Mcp::tool('list_projects', ListProjects::class)->annotations($readOnly);
Mcp::tool('get_project_progress', GetProjectProgress::class)->annotations($readOnly);
Mcp::tool('list_statuses', ListStatuses::class)->annotations($readOnly);
Mcp::tool('list_milestones', ListMilestones::class)->annotations($readOnly);

// Reading todos and their context.
Mcp::tool('list_todos', ListTodos::class)->annotations($readOnly);
Mcp::tool('get_todo', GetTodo::class)->annotations($readOnly);
Mcp::tool('list_comments', ListComments::class)->annotations($readOnly);
Mcp::tool('list_attachments', ListAttachments::class)->annotations($readOnly);
Mcp::tool('list_time_entries', ListTimeEntries::class)->annotations($readOnly);

/*
 * Writing. Each asserts the write grant before touching anything, and none of them delete.
 * destructiveHint is false because these only add or amend, never remove.
 */
$write = ToolAnnotations::make(readOnlyHint: false, destructiveHint: false);

Mcp::tool('create_todo', CreateTodo::class)->annotations($write);
Mcp::tool('update_todo', UpdateTodo::class)->annotations($write);
Mcp::tool('set_todo_status', SetTodoStatus::class)->annotations($write);
Mcp::tool('log_time', LogTime::class)->annotations($write);
Mcp::tool('add_comment', AddComment::class)->annotations($write);
