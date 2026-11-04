<?php
// read-only: how the published Whop product and its business look to Whop (visibility, discover, affiliates)
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$c = whop_cfg(); $pid = (string)(meta_get('whop_pub_product') ?? '');
$cut = function ($a, $d = 0) use (&$cut) { if (!is_array($a)) return is_string($a) ? mb_substr($a, 0, 160) : $a; if ($d > 2) return '…'; $o = []; foreach ($a as $k => $v) $o[$k] = $cut($v, $d + 1); return $o; };
$o = ['pid' => $pid, 'company' => $c['company']];
$p = whop_api('GET', '/products/' . preg_replace('/[^A-Za-z0-9_]/', '', explode('"', $pid)[0] ?: 'prod_w4WSQJcQdrAUA'), null, $c); $o['product'] = [$p['code'], $cut($p['data'] ?? $p['error'] ?? null)];
$b = whop_api('GET', '/companies/' . $c['company'], null, $c); $o['company_r'] = [$b['code'], $cut($b['data'] ?? $b['error'] ?? null)];
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
