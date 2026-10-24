<?php
// Dar — read-only (nothing is changed): everything that went wrong on the live site in the last day and a half, for finding the
// services a client says do not work well — the pages' errors, the server's, the AI calls, the AI jobs, the self-check's incidents,
// the automations, the mail, the beacons. Run once by a temporary cron job, then removed.
$R = '/home/u791571885/domains/dar.ink/public_html'; require $R . '/dar-sync.php';
$S = time() - 36 * 3600; $Sms = $S * 1000; $o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
$tq = function (string $sql, array $a = []) { try { return q($sql, $a)->fetchAll(); } catch (Throwable $e) { return 'ERR ' . $e->getMessage(); } };
$pg = []; foreach (@file($R . '/.dar/errors.log') ?: [] as $l) { $x = json_decode($l, true); if (!is_array($x) || strtotime((string)($x['t'] ?? '')) < $S) continue; $k = ($x['app'] ?? '') . '|' . ($x['route'] ?? '') . '|' . mb_substr((string)($x['msg'] ?? ''), 0, 110) . '|' . ($x['where'] ?? ''); $pg[$k] = ($pg[$k] ?? 0) + max(1, (int)($x['count'] ?? 1)); }
arsort($pg); $o['pages'] = array_slice($pg, 0, 40, true);
$se = log_tail(data_dir() . '/server-errors.log', 36 * 3600); $o['server'] = array_map(fn($g) => [$g['n'], $g['where'], mb_substr($g['msg'], 0, 140)], array_slice($se['groups'], 0, 15));
$o['ai_log'] = $tq("SELECT provider, SUM(ok) ok, SUM(ok = 0) bad, ROUND(AVG(ms)) ms FROM ai_log WHERE at > ? GROUP BY provider ORDER BY bad DESC", [$Sms]);
$o['ai_log_codes'] = $tq("SELECT provider, code, COUNT(*) n FROM ai_log WHERE at > ? AND ok = 0 GROUP BY provider, code ORDER BY n DESC LIMIT 20", [$Sms]);
$o['ai_jobs'] = $tq("SELECT kind, status, COUNT(*) n, MAX(err) err FROM ai_jobs WHERE updated > ? GROUP BY kind, status ORDER BY n DESC LIMIT 30", [$Sms]);
$o['lrn_tenant'] = $tq("SELECT fn, COUNT(*) n, SUM(ok = 0) bad, ROUND(AVG(ms)) ms, MAX(ms) mx, GROUP_CONCAT(DISTINCT code) codes FROM lrn_calls WHERE scope = 'tenant' AND at > ? GROUP BY fn ORDER BY bad DESC, mx DESC LIMIT 30", [$Sms]);
$o['lrn_all_bad'] = $tq("SELECT fn, scope, COUNT(*) n, SUM(ok = 0) bad, GROUP_CONCAT(DISTINCT code) codes FROM lrn_calls WHERE at > ? GROUP BY fn, scope HAVING bad > 0 ORDER BY bad DESC LIMIT 25", [$Sms]);
$o['incidents'] = $tq("SELECT code, subject, layer, level, status FROM doc_inc WHERE status != 'closed' ORDER BY rowid DESC LIMIT 25");
$o['flows'] = $tq("SELECT ns, status, COUNT(*) n, MAX(err) err FROM flow_runs WHERE started > ? GROUP BY ns, status", [$Sms]);
$o['mail'] = $tq("SELECT via, ok, COUNT(*) n, MAX(err) err FROM mail_log WHERE at > ? GROUP BY via, ok", [$Sms]);
$o['beacons'] = $tq("SELECT kind, app, COUNT(*) n, MAX(msg) msg FROM beacons WHERE at > ? GROUP BY kind, app ORDER BY n DESC LIMIT 25", [$S]);
$o['beacons_ms'] = $tq("SELECT kind, app, COUNT(*) n, MAX(msg) msg FROM beacons WHERE at > ? GROUP BY kind, app ORDER BY n DESC LIMIT 25", [$Sms]);
$o['sessions_24h'] = $tq("SELECT u.app, u.tenant, COUNT(*) n FROM sessions s JOIN users u ON u.id = s.uid WHERE s.created > ? GROUP BY u.app, u.tenant", [$Sms]);
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
