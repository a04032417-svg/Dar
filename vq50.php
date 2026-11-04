<?php
// read-only: the funnel — accounts by app and status, and the owners who signed up and never paid (warm leads)
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
foreach (q("SELECT app, status, COUNT(*) n FROM tenants GROUP BY app, status")->fetchAll() as $r) $o['by'][] = [$r['app'], $r['status'], (int)$r['n']];
$o['payments'] = (int)(one("SELECT COUNT(*) n FROM payments")['n'] ?? 0);
foreach (q("SELECT t.id, t.app, t.status, t.rec, t.created, u.email, u.rec urec FROM tenants t JOIN users u ON u.tenant = t.id AND u.role = 'owner' WHERE t.created > ? ORDER BY t.created DESC LIMIT 80", [(time() - 120 * 86400) * 1000])->fetchAll() as $r) {
  $T = json_decode((string)$r['rec']) ?: new stdClass(); $U = json_decode((string)$r['urec']) ?: new stdClass();
  $o['recent'][] = ['app' => $r['app'], 'st' => $r['status'], 'why' => $T->suspend_reason ?? '', 'aud' => $T->audience ?? '', 'plan' => $T->plan ?? '', 'cc' => $T->country ?? '', 'name' => mb_substr((string)($T->name ?? ''), 0, 60), 'owner' => mb_substr((string)($U->name ?? ''), 0, 40), 'lang' => $U->lang ?? '', 'email' => $r['email'], 'phone' => $T->phone ?? ($U->phone ?? ''), 'at' => gmdate('Y-m-d', (int)($r['created'] / 1000)), 'paid' => (int)(one("SELECT COUNT(*) n FROM payments WHERE ns = ?", [$r['id']])['n'] ?? 0)];
}
echo json_encode($o, JSON_UNESCAPED_UNICODE);
