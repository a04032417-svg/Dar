<?php
// read-only: the live partner (marketer) network and the suspended/past_due owners — the warmest, opted-in audience already in Dar
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['now' => gmdate('c')];
foreach (q("SELECT t.id, t.code, t.status, t.rec, u.email, u.rec urec FROM tenants t LEFT JOIN users u ON u.tenant = t.id AND u.role = 'owner' WHERE t.app = 'partner' AND t.status = 'active' ORDER BY t.created")->fetchAll() as $r) {
  $T = json_decode((string)$r['rec']) ?: new stdClass(); $U = json_decode((string)$r['urec']) ?: new stdClass();
  if (!empty($T->demo) || !empty($T->reviewer)) continue;
  $o['partners'][] = ['code' => $r['code'], 'name' => mb_substr((string)($U->name ?? ($T->name ?? '')), 0, 40), 'lang' => $U->lang ?? ($T->demo_lang ?? 'ar'), 'cc' => $T->country ?? '', 'email' => (string)$r['email'], 'has_email' => $r['email'] && strpos((string)$r['email'], '@')];
}
foreach (q("SELECT t.id, t.app, t.status, t.rec, u.email, u.rec urec FROM tenants t LEFT JOIN users u ON u.tenant = t.id AND u.role = 'owner' WHERE t.app IN ('app','builders') AND t.status IN ('suspended','past_due','trial') AND t.demo IS NOT 1 ORDER BY t.created")->fetchAll() as $r) {
  $T = json_decode((string)$r['rec']) ?: new stdClass(); $U = json_decode((string)$r['urec']) ?: new stdClass();
  if (!empty($T->demo) || !empty($T->reviewer)) continue;
  $o['winback'][] = ['app' => $r['app'], 'st' => $r['status'], 'why' => $T->suspend_reason ?? '', 'name' => mb_substr((string)($U->name ?? ''), 0, 40), 'office' => mb_substr((string)($T->name ?? ''), 0, 50), 'lang' => $U->lang ?? 'ar', 'email' => (string)$r['email'], 'ev_ok' => !empty($U->email_ok_at)];
}
$o['app_dir'] = ['app' => app_dir('app'), 'builders' => app_dir('builders'), 'partner' => app_dir('partner')]; $o['site'] = site_url();
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
