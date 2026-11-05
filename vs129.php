<?php
// The platform's measurement tags as the live site loads them (ids only), and how often the site's own sign-up and purchase moments happen
ini_set('display_errors', '0'); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$P = platform_rec(); $tr = o2a($P->tracking ?? null) ?: [];
echo json_encode(['ver' => SYNC_VERSION, 'tracking' => $tr, 'landing_keys' => array_keys(o2a($P->landing ?? null) ?: [])], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
