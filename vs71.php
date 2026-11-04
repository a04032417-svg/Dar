<?php
// The owner, 2026-11-04 19:14 Riyadh: «اجعل الباقات الحالية يبدأ سعرها من 200 دولار وخصم 50% (100) لمدة 7 أيام وتجربة 5 أيام ولكن مع إدخال البطاقة أيضًا»
// — Basic (offices) $200, every other plan of every audience in the same proportion (×200/399); 50% off on all until 2026-11-11; 5 free days; the card step unchanged.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$price = ['basic' => 200, 'advanced' => 250, 'enterprise' => 365, 'b_starter' => 225, 'b_growth' => 275, 'b_enterprise' => 375];
$k = 'db:platform:plans'; $rows = bill_rows('platform', $k); $out = ['changed' => [], 'backup' => ''];
$out['backup'] = 'plans-' . gmdate('Ymd-His') . '.json'; file_put_contents('/home/u791571885/' . $out['backup'], jenc($rows));
foreach ($rows as $x) {
  $c = (string)($x->code ?? ''); if (!isset($price[$c])) continue;
  $x->base_price = $price[$c]; $x->discount_until = '2026-11-11'; $x->discount_pct = 50; // (the owner, 19:20: «بنفس الطريقة» — the same 50% on every plan of every audience)
  $x->trial_days = 5; if (isset($x->trial_days_month) && $x->trial_days_month !== '') $x->trial_days_month = 5;
  $out['changed'][] = [$c, $x->base_price, (float)$x->discount_pct, $x->discount_until, $x->trial_days, round($x->base_price * (1 - (float)$x->discount_pct / 100), 2)];
}
bill_kv_put('platform', $k, jenc(array_values($rows)));
// Whop: the public plans at the new prices; the old-price variants hidden
$old = whop_pub_plans(); $pub = whop_publish(); $out['whop'] = $pub;
$new = whop_pub_plans(); $out['hidden'] = [];
foreach ($old as $id => $v) if (!isset($new[$id]) || (float)$new[$id][2] !== (float)$v[2]) { $r = whop_api('PATCH', '/plans/' . $id, ['visibility' => 'hidden']); $out['hidden'][] = [$id, $r['code']]; }
foreach ($new as $id => $v) { $keep = false; foreach ($price as $c => $p) { $row = null; foreach ($rows as $x) if ((string)($x->code ?? '') === $c) $row = $x; if ($row && (string)$v[1] === $c && abs((float)$v[2] - whop_pub_price($row)) < 0.01) $keep = true; } if (!$keep) { $r = whop_api('PATCH', '/plans/' . $id, ['visibility' => 'hidden']); $out['hidden'][] = [$id, $r['code'], 'stale']; } }
echo json_encode($out, JSON_UNESCAPED_UNICODE);
