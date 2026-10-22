<?php
// Read only: the live site's storage shape — each table with its row count, and the NAMES (never the values) of its settings,
// grouped (account ids and numbers folded), so the fixes for the owner's notes go where his data really is.
$W = '/home/u791571885/domains/dar.ink/public_html'; chdir($W); require $W . '/dar-sync.php';
$fold = fn($k) => preg_replace(['/\b(t|u|d|p|pr|n|w|sq|dr)[a-z0-9]{8,}\b/', '/[0-9]{3,}/'], ['<id>', 'N'], $k);
$out = ['server' => SYNC_VERSION, 'tables' => [], 'kv' => [], 'meta' => []];
foreach (q("SELECT name FROM sqlite_master WHERE type = 'table' ORDER BY name")->fetchAll() as $t) { $n = (string)$t['name']; $out['tables'][$n] = (int)(one('SELECT COUNT(*) n FROM "' . str_replace('"', '', $n) . '"')['n'] ?? 0); }
foreach (['kv', 'meta'] as $tb) { if (!isset($out['tables'][$tb])) continue; $g = [];
    foreach (q("SELECT k, LENGTH(v) l FROM $tb")->fetchAll() as $r) { $f = $fold((string)$r['k']); $g[$f] = ($g[$f] ?? 0) + 1; }
    ksort($g); $out[$tb] = $g; }
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
