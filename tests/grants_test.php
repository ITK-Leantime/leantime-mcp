<?php

/*
 * Self-check for the grant logic the MCP tools depend on.
 *
 * Guards the security-critical assertions in Mcp\ApiUserContext: a key must not reach a
 * project it was not granted, and a read-only key must not perform writes. Runs standalone
 * (no Leantime bootstrap, no database):
 *
 *   php app/Plugins/LeantimeMcp/tests/grants_test.php
 */

require_once __DIR__.'/../../Databridge/Model/Operation.php';
require_once __DIR__.'/../../Databridge/Model/ApiUser.php';

use Leantime\Plugins\Databridge\Model\ApiUser;
use Leantime\Plugins\Databridge\Model\Operation;

$readOnly = new ApiUser('reporting', [Operation::Read], [3]);
$writer = new ApiUser('sync', [Operation::Read, Operation::Write], [4]);
$unrestricted = new ApiUser('admin-agent', [Operation::Read, Operation::Write], null);

// Operation grants.
assert($readOnly->can(Operation::Read) === true, 'read-only key must hold read');
assert($readOnly->can(Operation::Write) === false, 'read-only key must NOT hold write');
assert($writer->can(Operation::Write) === true, 'writer key must hold write');
assert($writer->can(Operation::Delete) === false, 'writer key must NOT hold delete implicitly');

// Project scope: the granted project is reachable, others are not.
assert($readOnly->canAccessProject(3) === true, 'granted project must be reachable');
assert($readOnly->canAccessProject(4) === false, 'ungranted project must be refused');
assert($writer->canAccessProject(4) === true, 'granted project must be reachable');
assert($writer->canAccessProject(3) === false, 'ungranted project must be refused');

// The explicit "all" sentinel (null) reaches every project.
assert($unrestricted->canAccessProject(1) === true, '"all" sentinel must reach any project');
assert($unrestricted->canAccessProject(999) === true, '"all" sentinel must reach any project');

// A string project id must not slip past the int-typed grant check.
assert($readOnly->canAccessProject((int) '4') === false, 'cast string id must still be refused');

echo "grants_test: all assertions passed\n";
