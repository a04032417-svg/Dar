<?php
// read-only: the last refusal Whop gave (no secrets in it) and the platform's payment-error notes
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
echo json_encode(['v' => SYNC_VERSION, 'err' => meta_get('whop_last_err'), 'ready' => function_exists('whop_cfg') ? (function($c){ return ['env'=>$c['env']??null,'company_set'=>($c['company']??'')!=='','key_set'=>($c['key']??'')!=='']; })(whop_cfg()) : null], JSON_UNESCAPED_UNICODE), "\n";
