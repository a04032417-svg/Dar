<?php
// Dar — read-only (nothing is changed): the WhatsApp gateway's own answer and each linked number's state as the gateway sees it.
require '/home/u791571885/domains/dar.ink/public_html/dar-sync.php';
$o = ['health' => wag_health()];
foreach (['tmvlc7zhwu5pu8w', 'platform'] as $sid) { $x = wag_call('GET', '/v1/sessions/' . $sid, null, 20); $j = $x['json'] ?? null; if (is_array($j)) { unset($j['qr'], $j['code']); if (isset($j['phone'])) $j['phone'] = '…' . substr((string)$j['phone'], -4); } $o[$sid] = ['http' => $x['code'], 'err' => $x['err'], 'j' => $j]; }
$o['db'] = q('SELECT sid, status, at FROM wag_sessions')->fetchAll();
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
