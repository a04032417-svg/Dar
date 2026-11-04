<?php
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = [];
foreach (bill_platform_rows('plans') as $x) { $r = []; foreach (['audience', 'code', 'name_en', 'base_price', 'discount_pct', 'discount_until', 'trial_days', 'trial_days_month', 'trial_days_year', 'active', 'sort', 'id'] as $f) if (isset($x->$f)) $r[$f] = $x->$f; $o['plans'][] = $r; }
$o['trial_cfg'] = function_exists('trial_cfg') ? trial_cfg() : 'none';
$o['whop_pub'] = jdec((string)(meta_get('whop_pub_plans') ?? ''));
foreach (bill_platform_rows('trial_plans') as $x) { $r = []; foreach (['code', 'audience', 'days', 'trial_days', 'card_required', 'active', 'plan', 'plans', 'scope', 'id'] as $f) if (isset($x->$f)) $r[$f] = $x->$f; $o['trial_plans'][] = $r; }
$o['signup_card'] = function_exists('trial_signup_card') ? 'fn' : 'n/a';
echo json_encode($o, JSON_UNESCAPED_UNICODE);
