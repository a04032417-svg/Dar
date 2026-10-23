<?php
// Read only: the Analytics ID saved, and where the requirements now stand (ids only).
$W = '/home/u791571885/domains/dar.ink/public_html'; chdir($W); require $W . '/dar-sync.php';
$P = platform_rec(); $ctx = rq_ctx(); $st = [];
foreach (['ga4', 'search_console', 'wa_gateway', 'email_dns', 'app_google', 'master_key'] as $id) { $d = rq_def($id); $st[$id] = $d ? (rq_item($d, $ctx)['status'] ?? '?') : 'none'; }
echo json_encode(['at' => gmdate('c'), 'tracking' => $P->tracking ?? null, 'states' => $st], JSON_UNESCAPED_SLASHES), "\n";
