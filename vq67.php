<?php
// Live funnel probe (no personal data): tenants by app/status, trials past/future, signups by day, mail kinds sent to owners.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = []; $now = time();
foreach (q("SELECT app, status, COUNT(*) n FROM tenants GROUP BY app, status")->fetchAll() as $r) $o['by'][$r['app'] . '/' . $r['status']] = (int)$r['n'];
$o['trial'] = ['future' => 0, 'past' => 0, 'none' => 0, 'demo' => 0];
foreach (q("SELECT rec FROM tenants WHERE status = 'trial'")->fetchAll() as $r) {
  $T = json_decode((string)$r['rec']); if (!$T) continue;
  if (!empty($T->demo)) { $o['trial']['demo']++; continue; }
  $te = (string)($T->trial_end ?? ''); if ($te === '') $o['trial']['none']++; elseif (strtotime($te) > $now) $o['trial']['future']++; else $o['trial']['past']++;
}
$o['paid_ever'] = 0; foreach (q("SELECT rec FROM tenants")->fetchAll() as $r) { $T = json_decode((string)$r['rec']); if ($T && !empty($T->first_paid_at)) $o['paid_ever']++; }
foreach (q("SELECT date(created/1000, 'unixepoch') d, app, COUNT(*) n FROM tenants WHERE created > ? GROUP BY d, app ORDER BY d", [($now - 14 * 86400) * 1000])->fetchAll() as $r) $o['signups'][$r['d'] . ' ' . $r['app']] = (int)$r['n'];
foreach (q("SELECT kind, ok, COUNT(*) n FROM mail_log WHERE at > ? GROUP BY kind, ok", [($now - 7 * 86400) * 1000])->fetchAll() as $r) $o['mail7d'][$r['kind'] . '/' . ($r['ok'] ? 'ok' : 'fail')] = (int)$r['n'];
$o['sites'] = (int)one("SELECT COUNT(DISTINCT ns) n FROM pub")['n'];
$o['pub_views'] = (int)one("SELECT COALESCE(SUM(views),0) n FROM pub")['n'];
echo json_encode($o, JSON_UNESCAPED_UNICODE);
