<?php
// Probe: what each trial account actually did (no names, no addresses; ids hashed) and where the platform refused people
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = []; $d = fn($ms) => $ms ? gmdate('m-d H:i', intdiv((int)$ms, 1000)) : '';
foreach (q("SELECT id, app, status, rec, created FROM tenants WHERE app != 'partner' ORDER BY created")->fetchAll() as $r) {
  $T = json_decode((string)$r['rec']) ?: new stdClass(); $id = (string)$r['id'];
  $cols = []; foreach (q("SELECT k, sz FROM kv WHERE k LIKE ?", ['db:' . $id . ':%'])->fetchAll() as $x) { $c = substr((string)$x['k'], strlen('db:' . $id . ':')); $cols[$c] = (int)$x['sz']; }
  arsort($cols); $uids = array_column(q("SELECT id FROM users WHERE tenant = ?", [$id])->fetchAll(), 'id');
  $last = 0; foreach ($uids as $u) $last = max($last, (int)(one("SELECT MAX(created) m FROM sessions WHERE uid = ?", [$u])['m'] ?? 0));
  $o['t'][] = [substr(hash('sha256', $id), 0, 6), $r['app'], $r['status'], (string)($T->suspend_reason ?? ''), $d($r['created']), substr((string)($T->trial_end ?? ''), 5, 11), empty($T->demo) ? 0 : 1, empty($T->first_paid_at) ? 0 : 1, (string)($T->plan ?? ''), count($uids), 'last_login ' . $d($last), array_slice($cols, 0, 6, true), (int)(one("SELECT COALESCE(SUM(views),0) n FROM pub WHERE ns = ?", [$id])['n'] ?? 0), (int)(one("SELECT COALESCE(SUM(n),0) n FROM ai_usage WHERE ns = ?", [$id])['n'] ?? 0)];
}
try { foreach (q("SELECT app, op, code, SUM(n) n, MAX(last) l FROM op_refused WHERE day >= ? GROUP BY app, op, code ORDER BY n DESC LIMIT 25", [gmdate('Y-m-d', time() - 14 * 86400)])->fetchAll() as $r) $o['refused'][] = [$r['app'], $r['op'], $r['code'], (int)$r['n'], $d($r['l'])]; } catch (Throwable $e) { $o['refused'] = $e->getMessage(); }
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
