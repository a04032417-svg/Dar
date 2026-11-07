<?php
// Read-only probe: how ai_complete() is called and what it returns. Changes nothing.
ini_set('display_errors', '1'); error_reporting(E_ALL); $R = '/home/u791571885/domains/dar.ink/public_html';
$s = file_get_contents("$R/dar-ai.php"); $L = explode("\n", $s);
foreach ($L as $i => $l) if (preg_match('/function ai_complete\(/', $l)) { echo "== ai_complete @", $i + 1, "\n", implode("\n", array_slice($L, max(0, $i - 12), 45)), "\n"; break; }
$n = 0;
foreach (glob("$R/dar-*.php") as $f) { $T = explode("\n", file_get_contents($f));
  foreach ($T as $i => $l) if (strpos($l, 'ai_complete(') !== false && strpos($l, 'function ai_complete') === false) {
    echo "== ", basename($f), ":", $i + 1, "\n", implode("\n", array_slice($T, max(0, $i - 4), 9)), "\n"; if (++$n >= 5) break 2; } }
