<?php
// Read only: every requirement of the platform and where it stands (no value, no key — only ids, states, the fields still empty and
// the provider's page), to know which ones can still be obtained.
$W = '/home/u791571885/domains/dar.ink/public_html'; chdir($W); require $W . '/dar-sync.php';
$mask = fn(string $s) => function_exists('keys_scrub_all') ? keys_scrub_all($s) : preg_replace('/[A-Za-z0-9_\-]{24,}/', '[…]', $s);
$ctx = rq_ctx(); $rows = [];
foreach (rq_defs() as $d) {
  $it = rq_item($d, $ctx); $st = (string)($it['status'] ?? '');
  if ($st === 'ok') { $rows['ok'][] = $d['id']; continue; }
  $rows[$st ?: 'other'][] = $d['id'] . ' [' . ($d['need'] ?? '') . '/' . ($d['cost'] ?? '') . '] ' . (($it['last']['code'] ?? '') !== '' ? 'code=' . $it['last']['code'] . ' ' : '') . 'missing=' . implode(',', array_map(fn($x) => is_array($x) ? ($x['k'] ?? $x['key'] ?? '?') : (string)$x, rq_missing_fields($it))) . ' ' . ($d['url'] ?? '') . (($it['last']['msg'] ?? '') !== '' ? ' — ' . mb_substr($mask((string)$it['last']['msg']), 0, 140) : '');
}
$r = rq_act_report();
echo json_encode(['at' => gmdate('c'), 'server' => SYNC_VERSION, 'by_status' => $rows, 'act' => ['failing' => array_column($r['failing'], 'id'), 'missing' => array_column($r['missing'], 'id'), 'resting' => array_column($r['resting'], 'id'), 'soon' => array_column($r['soon'], 'id'), 'not_needed' => array_column($r['not_needed'], 'id'), 'optional' => array_column($r['optional'], 'id')], 'readiness' => $r['readiness'] ?? null], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "\n";
