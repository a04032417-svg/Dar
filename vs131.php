<?php
// For the few accounts closest to paying: is anything real happening in them? Counts only (no names, no message text):
// per collection — how many rows, how many are the seeded starter examples, rows by day for the last 10 days, the channels named
ini_set('display_errors', '0'); @set_time_limit(60);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$want = ['242ec3', '2590ba', '27e886', 'fb74a8']; $o = ['ver' => SYNC_VERSION, 'now' => gmdate('c')];
$day = function ($r) { foreach (['created', 'at', 'ts', 'time', 'date', 'updated'] as $f) { $x = $r[$f] ?? null; if ($x === null || $x === '') continue;
  if (is_numeric($x)) { $n = (int)$x; if ($n > 1e12) $n = intdiv($n, 1000); return gmdate('m-d', $n); } $t = strtotime((string)$x); if ($t) return gmdate('m-d', $t); } return '?'; };
foreach (q('SELECT id, rec FROM tenants')->fetchAll() as $t) {
  $h = substr(md5($t['id']), 0, 6); if (!in_array($h, $want, true)) continue; $rec = json_decode((string)$t['rec'], true) ?: [];
  $out = ['utm' => $rec['utm'] ?? null, 'account_type' => $rec['account_type'] ?? '', 'colls' => []];
  foreach (q('SELECT k, upd, sz, v FROM kv WHERE ns = ?', [$t['id']])->fetchAll() as $row) {
    $k = preg_replace('/^(db|set):[^:]+:/', '$1:', (string)$row['k']); $v = json_decode((string)$row['v'], true);
    $c = ['upd' => gmdate('m-d H:i', intdiv((int)$row['upd'], 1000)), 'sz' => (int)$row['sz']];
    if (is_array($v) && array_is_list($v)) { $c['n'] = count($v); $c['ex'] = 0; $days = []; $ch = [];
      foreach ($v as $r) { if (!is_array($r)) continue; if (row_example($r)) { $c['ex']++; continue; } $days[$day($r)] = ($days[$day($r)] ?? 0) + 1;
        foreach (['channel', 'source', 'via', 'dir', 'direction', 'from_type'] as $f) if (isset($r[$f]) && is_scalar($r[$f])) $ch[$f . '=' . substr((string)$r[$f], 0, 20)] = ($ch[$f . '=' . substr((string)$r[$f], 0, 20)] ?? 0) + 1; }
      ksort($days); $c['days'] = $days; if ($ch) $c['ch'] = $ch; }
    $out['colls'][$k] = $c;
  }
  $o[$h] = $out;
}
echo json_encode($o, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
