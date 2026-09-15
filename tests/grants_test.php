<?php

/*
 * Self-check for the grant logic the MCP tools depend on.
 *
 * Guards the security-critical assertions in Mcp\ApiUserContext: a key must not reach a
 * project it was not granted, and a read-only key must not perform writes. Runs standalone
 * (no Leantime bootstrap, no database):
 *
 *   php app/Plugins/LeantimeMcp/tests/grants_test.php
 *
 * Deliberately does NOT use assert(): PHP compiles assertions out under
 * zend.assertions=-1, which is what the project's PHP-FPM container ships. A grant check
 * that silently passes when the logic is bypassed is worse than no check at all, so this
 * uses plain conditionals and a non-zero exit instead.
 */

require_once __DIR__ . '/../../Databridge/Model/Operation.php';
require_once __DIR__ . '/../../Databridge/Model/ApiUser.php';

use Leantime\Plugins\Databridge\Model\ApiUser;
use Leantime\Plugins\Databridge\Model\Operation;

/** @var list<string> $failures Widened for phpstan: check() appends via `global`, which it cannot track. */
$failures = [];

/**
 * Record a failure unless $actual matches $expected.
 *
 * @return void
 */
function check(string $description, bool $expected, bool $actual): void
{
    global $failures;

    if ($expected !== $actual) {
        $failures[] = sprintf(
            '%s (expected %s, got %s)',
            $description,
            $expected ? 'true' : 'false',
            $actual ? 'true' : 'false'
        );
    }
}

$readOnly = new ApiUser('reporting', [Operation::Read], [3]);
$writer = new ApiUser('sync', [Operation::Read, Operation::Write], [4]);
$unrestricted = new ApiUser('admin-agent', [Operation::Read, Operation::Write], null);

// Operation grants.
check('read-only key holds read', true, $readOnly->can(Operation::Read));
check('read-only key does NOT hold write', false, $readOnly->can(Operation::Write));
check('writer key holds write', true, $writer->can(Operation::Write));
check('writer key does NOT hold delete implicitly', false, $writer->can(Operation::Delete));

// Project scope: the granted project is reachable, others are not.
check('granted project is reachable', true, $readOnly->canAccessProject(3));
check('ungranted project is refused', false, $readOnly->canAccessProject(4));
check('writer reaches its granted project', true, $writer->canAccessProject(4));
check('writer is refused another project', false, $writer->canAccessProject(3));

// The explicit "all" sentinel (null) reaches every project.
check('"all" sentinel reaches project 1', true, $unrestricted->canAccessProject(1));
check('"all" sentinel reaches project 999', true, $unrestricted->canAccessProject(999));

// A string project id must not slip past the int-typed grant check.
check('cast string id is still refused', false, $readOnly->canAccessProject((int) '4'));

if ($failures !== []) {
    fwrite(STDERR, "grants_test FAILED:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '  - ' . $failure . "\n");
    }
    exit(1);
}

echo "grants_test: all checks passed\n";
