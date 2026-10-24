<?php
// Dar — read-only (nothing is changed): what the pages and the server reported in the last three days (grouped, the visitors' own
// details left out), for finding the services a client says do not work well.
$R = '/home/u791571885/domains/dar.ink/public_html'; require $R . '/dar-sync.php';
$S = time() - 72 * 3600; $o = ['v' => SYNC_VERSION, 'now' => gmdate('c')]; $pg = [];
foreach (@file($R . '/.dar/errors.log') ?: [] as $l) { $x = json_decode($l, true); if (!is_array($x) || ($t = strtotime((string)($x['t'] ?? ''))) < $S) continue;
  $k = ($x['sev'] ?? '') . '|' . ($x['app'] ?? '') . '|' . ($x['route'] ?? '') . '|' . mb_substr((string)($x['msg'] ?? ''), 0, 130) . '|' . mb_substr((string)($x['where'] ?? ''), 0, 60) . '|' . ($x['v'] ?? ($x['ver'] ?? ''));
  $g = $pg[$k] ?? ['n' => 0, 'last' => '']; $g['n'] += max(1, (int)($x['count'] ?? 1)); $g['last'] = max($g['last'], gmdate('m-d H:i', $t)); $pg[$k] = $g; }
uasort($pg, fn($a, $b) => $b['n'] <=> $a['n']); $o['pages'] = array_slice($pg, 0, 60, true);
$o['keys_seen'] = []; foreach (array_slice(@file($R . '/.dar/errors.log') ?: [], -1) as $l) { $x = json_decode($l, true); if (is_array($x)) $o['keys_seen'] = array_keys($x); }
$se = log_tail(data_dir() . '/server-errors.log', 72 * 3600); $o['server'] = array_map(fn($g) => [$g['n'], $g['where'], mb_substr($g['msg'], 0, 160)], array_slice($se['groups'], 0, 25));
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
