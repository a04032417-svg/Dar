<?php
// Dar — after installing 3.33.21 (nothing is created or changed): the version; whether the Platform's Whop key may make the partners'
// payout accounts and send their payouts to their banks — asked with requests Whop refuses for a missing field (a 400 means the key is
// allowed, a 403 that it lacks the permission), so nothing is made; the subscribers' transfer still off; what the server refused.
$R = '/home/u791571885/domains/dar.ink/public_html'; require $R . '/dar-sync.php';
$tq = function (string $sql, array $a = []) { try { return q($sql, $a)->fetchAll(); } catch (Throwable $e) { return 'ERR ' . $e->getMessage(); } };
$c = po_cfg('whopbank'); $probe = function (string $m, string $p, $b) use ($c) { $r = whop_api($m, $p, $b, $c['w']); return ['http' => $r['code'], 'why' => mb_substr(whop_why($r), 0, 160)]; };
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c'), 'engine' => po_engine('iban'), 'whop_ready' => po_ready('whop'), 'iban_ready' => po_ready('iban'),
  'probe' => ['accounts' => $probe('POST', '/accounts', ['email' => 'not-an-email', 'country' => 'ZZ', 'title' => '']), 'account_links' => $probe('POST', '/account_links', ['use_case' => 'payouts_portal']), 'payouts' => $probe('POST', '/payouts', ['amount' => 0]), 'payout_methods' => $probe('GET', '/payout_methods?company_id=' . rawurlencode($c['company']), null)],
  'limits' => ['plan_limit' => function_exists('plan_limit'), 'msgs_unit' => defined('QUOTA_UNITS') && isset(QUOTA_UNITS['msgs']), 'plans' => array_map(fn($p) => [(string)($p->code ?? ''), (string)($p->audience ?? ''), $p->limits ?? null], array_values(array_filter(jdec(kv_get('platform', 'db:platform:plans')) ?: [], 'is_object')))], 'docx_js' => is_file($R . '/assets/docx.js') ? hash_file('sha256', $R . '/assets/docx.js') : null,
  'transfer_feature' => feat_own(platform_rec(), 'pay.transfer')['state'] ?? null, 'refused' => $tq("SELECT day, app, op, code, n FROM op_refused ORDER BY n DESC LIMIT 20")];
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
