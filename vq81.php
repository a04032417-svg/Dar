<?php
// Probe: did anyone try to pay and fail? (counts, reasons, days, countries — no personal data)
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = [];
$o['checkout_events'] = q("SELECT day, kind, path reason, cc, app, COUNT(*) n FROM la_events WHERE kind IN ('checkout', 'checkout_error', 'signup', 'signup_error', 'signup_start', 'signup_submit') AND at > ? GROUP BY day, kind, path, cc, app ORDER BY day", [now_ms() - 14 * 86400000])->fetchAll();
$o['checkout_err_last'] = q("SELECT day, hr, path reason, app, substr(tenant,1,6) t FROM la_events WHERE kind = 'checkout_error' ORDER BY at DESC LIMIT 12")->fetchAll();
$o['whop_pub'] = jdec((string)(meta_get('whop_pub_plans') ?? ''));
$o['pay_cfg'] = function_exists('pay_cfg') ? array_intersect_key(pay_cfg(), array_flip(['provider', 'mode', 'ok', 'live'])) : 'n/a';
$o['invoices'] = q("SELECT substr(ns,1,2) x, COUNT(*) n FROM kv WHERE k LIKE 'db:%:invoices'")->fetchAll();
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
