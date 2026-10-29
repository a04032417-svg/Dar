<?php
// Dar — read-only look at the WhatsApp gateway after its update to 1.0.6 (2026-10-29): its version, the open session, and whether the phone's address
// book reached it (counts only; whether three names the owner uses are found — no number or name is printed). Nothing is changed.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')]; $h = wag_health(); $o['gw'] = ['v' => $h['v'] ?? null, 'up_s' => $h['up'] ?? null, 'sessions' => $h['sessions'] ?? null, 'platform_open' => wag_platform_open()];
foreach (['stc', 'وصفتي', 'مرام'] as $q) { $x = wag_call('GET', '/v1/sessions/platform/contacts?q=' . rawurlencode($q) . '&limit=3', null, 10); $j = $x['json'] ?? []; $o['q'][$q] = ['code' => $x['code'], 'found' => count($j['items'] ?? []), 'total' => $j['total'] ?? null, 'with_phone' => $j['with_phone'] ?? null, 'synced' => $j['synced'] ?? null, 'syncing' => $j['syncing'] ?? null, 'error' => $j['error'] ?? null, 'synced_at' => !empty($j['synced_at']) ? gmdate('c', (int)($j['synced_at'] / 1000)) : null]; }
echo json_encode($o, JSON_UNESCAPED_UNICODE), "\n";
