<?php
// One-time (the owner, 2026-10-23 in his panel: «ضع الموقع في وضع الصيانة» — the step stopped on his browser's full storage):
// maintenance on, with the message he wrote before; the change is written to the platform record (every page sees it) and its log.
$W = '/home/u791571885/domains/dar.ink/public_html'; chdir($W); require $W . '/dar-sync.php';
$P = jdec(kv_get('platform', 'platform')); if (!is_object($P)) { echo "no platform record\n"; exit(1); }
$was = jenc($P->maintenance ?? null);
if (!isset($P->maintenance) || !is_object($P->maintenance)) $P->maintenance = new stdClass();
$P->maintenance->on = true;
kv_put('platform', 'platform', jenc($P), 'server');
if (function_exists('audit_srv')) audit_srv(['user' => ['id' => 'owner-request', 'name' => 'المالك (عبر Claude)']], 'platform.set', 'maintenance.on: true');
echo jenc(['was' => json_decode($was), 'now' => $P->maintenance]), "\n";
