<?php
// Dar 3.33.47 — after the install: Dar's plans put on sale in Whop's marketplace (visible, open to Whop's affiliates), once; prints the product and the plans' checkout links
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php'; require_once $R . '/dar-pay.php'; require_once $R . '/dar-whop.php';
$o = ['v' => SYNC_VERSION, 'provider' => pay_provider()];
try { $o['publish'] = whop_publish(); } catch (Throwable $e) { $o['error'] = $e->getMessage(); }
$h = curl_init('https://dar.ink/llms.txt'); curl_setopt_array($h, [CURLOPT_NOBODY => 1, CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 10]); curl_exec($h); $o['llms'] = curl_getinfo($h, CURLINFO_HTTP_CODE); curl_close($h);
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
