<?php
// Dar — read-only (nothing is changed): the platform's settings as the offices' pages read them (features, services, plans, menus —
// anything that looks like a key or a token is blanked), the accounts' plans and countries (no names), to reproduce their screens.
$R = '/home/u791571885/domains/dar.ink/public_html'; require $R . '/dar-sync.php';
$P = jdec(kv_get('platform', 'platform'));
$scrub = function ($v) use (&$scrub) { if (is_object($v)) { foreach (get_object_vars($v) as $k => $x) { if (preg_match('/key|secret|token|pass|iban|smtp|webhook|private/i', (string)$k) && !is_object($x) && !is_array($x)) { $v->$k = ''; continue; } $v->$k = $scrub($x); } return $v; }
  if (is_array($v)) return array_map($scrub, $v); if (is_string($v) && preg_match('/^[A-Za-z0-9_\-.:\/+=]{28,}$/', $v) && !preg_match('#^https?://#', $v)) return '•'; return $v; };
$P = $scrub($P); $j = json_encode($P, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$t = array_map(fn($r) => ['app' => $r['app'], 'status' => $r['status'], 'plan' => (string)(jdec($r['rec'])->plan ?? ''), 'country' => (string)(jdec($r['rec'])->country ?? ''), 'audience' => (string)(jdec($r['rec'])->audience ?? '')], q('SELECT app, status, rec FROM tenants')->fetchAll());
echo json_encode(['v' => SYNC_VERSION, 'bytes' => strlen($j), 'keys' => array_keys((array)$P), 'tenants' => $t]), "\n";
echo base64_encode(gzencode($j, 9)), "\n";
