<?php
// Dar — read-only (nothing is changed): the partners' payouts (which payout services are linked — yes/no only, the partners' chosen
// ways, what is due), and everything the pages, the server and the AI reported since 3.33.19 was installed (09:32 UTC).
$R = '/home/u791571885/domains/dar.ink/public_html'; require $R . '/dar-sync.php';
$tq = function (string $sql, array $a = []) { try { return q($sql, $a)->fetchAll(); } catch (Throwable $e) { return 'ERR ' . $e->getMessage(); } };
$S = strtotime('2026-10-24T09:32:00Z'); $o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
foreach (['paypal', 'wise', 'payoneer', 'whop'] as $m) { $c = po_cfg($m); $o['po'][$m] = ['on' => $c['on'], 'linked' => po_linked($m, $c), 'ready' => po_ready($m, $c), 'env' => $c['env']] + ($m === 'wise' ? ['source' => $c['source'], 'sca_key' => ($c['key'] ?? '') !== ''] : []); }
$pp = bill_pp(); $o['pp'] = ['auto' => $pp['auto_payout'] ?? null, 'min' => $pp['min_payout'], 'min_count' => $pp['min_count'] ?? null, 'hold_days' => $pp['hold_days']];
$o['partners'] = [];
foreach (q("SELECT id, status FROM tenants WHERE app = 'partner'")->fetchAll() as $t) { $tid = (string)$t['id']; $prof = po_profile($tid); $list = bill_rows($tid, 'db:' . $tid . ':p_commissions'); $pos = bill_rows($tid, 'db:' . $tid . ':p_payouts');
  $o['partners'][] = ['status' => $t['status'], 'method' => po_method($prof), 'iban' => is_object($prof) && (string)($prof->payout_iban ?? '') !== '', 'name' => is_object($prof) && (string)($prof->payout_name ?? '') !== '', 'cur' => is_object($prof) ? (string)($prof->payout_currency ?? '') : '', 'commissions' => count($list), 'payable' => po_payable($list), 'payouts' => array_map(fn($x) => [(string)($x->status ?? ''), (string)($x->method ?? ''), (float)($x->amount ?? 0)], array_slice($pos, 0, 5))]; }
$o['feat'] = []; $P = platform_rec(); foreach (['partners.payouts', 'partners.program', 'pay.transfer'] as $f) $o['feat'][$f] = feat_own($P, $f); $o['feat_auto'] = array_intersect_key(feat_auto(), array_flip(['partners.payouts', 'partners.program', 'pay.transfer']));
$o['req_payout'] = function_exists('rq_defs') ? array_values(array_filter(array_keys(rq_defs()), fn($k) => str_contains((string)$k, 'payout') || str_contains((string)$k, 'wise') || str_contains((string)$k, 'whop'))) : null;
$pg = []; foreach (@file($R . '/.dar/errors.log') ?: [] as $l) { $x = json_decode($l, true); if (!is_array($x) || ($t = strtotime((string)($x['t'] ?? ''))) < $S) continue; $k = ($x['sev'] ?? '') . '|' . ($x['app'] ?? '') . '|' . ($x['route'] ?? '') . '|' . mb_substr((string)($x['msg'] ?? ''), 0, 160) . '|' . mb_substr((string)($x['where'] ?? ''), 0, 80) . '|' . ($x['v'] ?? ''); $pg[$k] = ($pg[$k] ?? 0) + max(1, (int)($x['count'] ?? 1)); }
arsort($pg); $o['pages'] = array_slice($pg, 0, 50, true);
$se = log_tail(data_dir() . '/server-errors.log', time() - $S); $o['server'] = array_map(fn($g) => [$g['n'], $g['where'], mb_substr($g['msg'], 0, 180)], array_slice($se['groups'], 0, 20));
$o['lrn'] = $tq("SELECT fn, scope, COUNT(*) n, SUM(ok = 0) bad, ROUND(AVG(ms)) ms, MAX(ms) mx, GROUP_CONCAT(DISTINCT provider) pv, GROUP_CONCAT(DISTINCT code) codes FROM lrn_calls WHERE at > ? GROUP BY fn, scope ORDER BY bad DESC, n DESC LIMIT 30", [$S * 1000]);
$o['ai_bad'] = $tq("SELECT CASE WHEN ns = 'platform' THEN 'p' ELSE 't' END w, provider, code, COUNT(*) n FROM ai_log WHERE at > ? AND ok = 0 GROUP BY w, provider, code ORDER BY n DESC LIMIT 20", [$S * 1000]);
$o['sessions'] = $tq("SELECT u.app, u.tenant, COUNT(*) n, MAX(s.created) last FROM sessions s JOIN users u ON u.id = s.uid WHERE s.created > ? GROUP BY u.app, u.tenant", [($S - 6 * 3600) * 1000]);
$o['audit'] = $tq("SELECT op, COUNT(*) n FROM audit WHERE at > ? GROUP BY op ORDER BY n DESC LIMIT 30", [$S * 1000]);
$o['incidents'] = $tq("SELECT code, subject, level, status, info FROM doc_inc WHERE status IN ('open', 'stuck') ORDER BY rowid DESC LIMIT 12");
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
