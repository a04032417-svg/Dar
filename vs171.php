<?php
// Read-only probe for the template factory: AI function signatures, cron dispatch, runtime limits. Changes nothing.
ini_set('display_errors', '1'); error_reporting(E_ALL); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R);
$o = ['php' => PHP_VERSION, 'max_exec' => ini_get('max_execution_time'), 'mem' => ini_get('memory_limit'),
      'disabled' => ini_get('disable_functions'), 'py' => function_exists('shell_exec') ? 'shell ok' : 'no shell'];
foreach (['dar-ai.php', 'dar-freeai.php', 'dar-brain.php', 'dar-mind.php'] as $f) {
  $s = @file_get_contents($R . '/' . $f); preg_match_all('/function\s+(\w+)\s*\(([^)]*)\)/', (string)$s, $m, PREG_SET_ORDER);
  $o['fn'][$f] = array_slice(array_map(fn($x) => $x[1] . '(' . preg_replace('/\s+/', ' ', $x[2]) . ')', $m), 0, 90);
}
$s = file_get_contents($R . '/dar-sync.php');
preg_match_all('/function\s+(\w+)\s*\(([^)]*)\)/', $s, $m, PREG_SET_ORDER);
$o['sync_ai_fns'] = array_values(array_filter(array_map(fn($x) => $x[1] . '(' . preg_replace('/\s+/', ' ', $x[2]) . ')', $m), fn($n) => preg_match('/^(ai|llm|chat|ask|gen|cron|job|tick|sched|lrn|q\b|one\b|kv|opt|setting)/i', $n)));
$lines = explode("\n", $s); $hits = [];
foreach ($lines as $i => $l) if (preg_match('/argv|=== .cron.|\bcron_run|function cron/', $l)) { $hits[] = $i; if (count($hits) > 6) break; }
foreach ($hits as $h) $o['cron_ctx'][] = implode("\n", array_slice($lines, max(0, $h - 2), 14));
$o['sync_requires'] = array_slice(array_values(array_unique(preg_match_all('/require(?:_once)?\s*\(?\s*[^;]*?[\'"]([\w\-\.\/]+\.php)[\'"]/', $s, $mm) ? $mm[1] : [])), 0, 80);
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR), "\n";
