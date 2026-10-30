<?php
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php'; require_once $R . '/dar-pay.php'; require_once $R . '/dar-whop.php';
$o = [];
foreach (bill_platform_rows('plans') as $p) if (($p->audience ?? '') === 'builder') $o['pl'][] = [$p->code, $p->name_en ?? '', $p->discount_pct ?? '', $p->discount_until ?? ''];
foreach ([['builder', 'b_starter', 'SA'], ['builder', 'b_starter', 'DE'], ['office', 'basic', 'SA']] as [$a, $pl, $cc]) {
  $T = (object)['id' => 'probe', 'audience' => $a, 'country' => $cc, 'plan' => $pl, 'status' => 'active'];
  try { $q = pay_quote($T, $pl, 'month', '', []); $tm = pay_terms($q); $sku = pay_sku($T, $q, false);
    $o['q'][] = [$a, $pl, $cc, 'cur' => $q['currency'], 'mode' => $q['mode'], 'rate' => $q['rate'], 'plan_price' => $q['plan_price'], 'list' => $q['list_price'], 'pct' => $q['plan_pct'], 'sub' => $q['subtotal'], 'total' => $q['total'], 'terms' => $tm, 'sku_sum' => $sku['sum'], 'incl' => $sku['incl'], 'cur_whop' => whop_cur((string)$q['currency']), 'WHOP_CUR' => defined('WHOP_CUR') ? WHOP_CUR : null]; } catch (Throwable $e) { $o['q'][] = [$cc, $e->getMessage()]; }
}
echo json_encode($o, JSON_UNESCAPED_UNICODE);
