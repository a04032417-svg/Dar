<?php
// After 3.33.49: did the win-back reach the read-only accounts? counts only.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['winback_marked' => 0, 'suspended' => 0];
foreach (q("SELECT rec FROM tenants WHERE status = 'suspended' AND app != 'partner'")->fetchAll() as $r) { $T = json_decode((string)$r['rec']); if (!$T) continue; $o['suspended']++; if (is_object($T->bill ?? null) && is_object($T->bill->sent ?? null) && (isset($T->bill->sent->{'winback:3'}) || isset($T->bill->sent->{'winback:14'}))) $o['winback_marked']++; }
foreach (q("SELECT kind, ok, via, COUNT(*) n FROM mail_log WHERE kind = 'promo' AND at > ? GROUP BY kind, ok, via", [now_ms() - 3600000])->fetchAll() as $r) $o['promo_1h'][$r['ok'] ? 'ok' : 'fail'][$r['via']] = (int)$r['n'];
$o['held'] = (int)one("SELECT COUNT(*) n FROM mail_log WHERE via = 'held' AND at > ?", [now_ms() - 3600000])['n'];
echo json_encode($o);
