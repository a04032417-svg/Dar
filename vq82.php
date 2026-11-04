<?php
// Probe: can a subscriber pay right now? (provider, readiness, the public checkout pages answer) — no secrets printed
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['provider' => function_exists('pay_provider') ? pay_provider() : 'n/a'];
if (function_exists('whop_cfg')) { $w = whop_cfg(); $o['whop'] = ['ready' => whop_ready($w), 'on' => $w['on'], 'env' => $w['env'], 'key' => $w['key'] !== '', 'secret' => $w['secret'] !== '', 'company' => $w['company'] !== '']; }
$o['pay_info'] = function_exists('pay_info') ? pay_info() : null;
foreach (['plan_IQewcPYNz2632', 'plan_aBzDyQWLJOUAI'] as $pl) { $c = curl_init('https://whop.com/checkout/' . $pl . '/'); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_FOLLOWLOCATION => true, CURLOPT_USERAGENT => 'Mozilla/5.0']); $b = (string)curl_exec($c); $o['checkout'][$pl] = [curl_getinfo($c, CURLINFO_HTTP_CODE), preg_match('~<title>([^<]*)</title>~', $b, $m) ? mb_substr($m[1], 0, 80) : '']; curl_close($c); }
$o['last_whop_log'] = function_exists('log_tail') ? log_tail($R . '/.dar/errors.log', 7 * 86400, 'error')['groups'] ?? null : null;
if (is_array($o['last_whop_log'])) $o['last_whop_log'] = array_values(array_filter(array_map(fn($g) => mb_substr((string)$g['msg'], 0, 140) . ' @' . $g['where'] . ' x' . ($g['n'] ?? ''), $o['last_whop_log']), fn($s) => stripos($s, 'whop') !== false || stripos($s, 'pay') !== false || stripos($s, 'checkout') !== false));
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
