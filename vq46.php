<?php
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php'; require_once $R . '/dar-pay.php';
$P = platform_rec(); $o = ['base_cur' => $P->base_currency ?? null, 'base_usd_rate' => $P->base_usd_rate ?? null, 'price_display' => $P->price_display ?? null, 'world' => $P->world ?? null];
foreach (bill_platform_rows('countries') as $c) $o['countries'][] = [$c->code ?? '', $c->currency ?? '', $c->bill_currency ?? '', $c->usd_rate ?? '', $c->tax_rate ?? '', $c->price_display ?? ''];
foreach (bill_platform_rows('plans') as $p) $o['plans'][] = [$p->audience ?? '', $p->code ?? '', $p->base_price ?? '', $p->discount_pct ?? '', $p->months_free ?? '', json_encode($p->prices ?? null), $p->sort ?? ''];
$o['cfg'] = function_exists('bill_cfg') ? bill_cfg($P) : null;
echo json_encode($o, JSON_UNESCAPED_UNICODE);
