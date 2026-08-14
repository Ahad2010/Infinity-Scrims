<?php
/** Owner: permanently remove one result entry (row) from a result under review. */
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');
Auth::requireOwner();
Auth::verifyCsrf();

$d = require_fields(['entry_id']);
$entry = DB::one("SELECT * FROM result_entries WHERE id=?", [(int)$d['entry_id']]);
if (!$entry) fail('Entry not found.', 404);

DB::run("DELETE FROM result_entries WHERE id=?", [$entry['id']]);

ok([], 'Entry removed.');