<?php
// Dar — read-only (nothing is changed): what went wrong on the live site since the last update (3.33.18, 08:13 UTC) — the pages'
// errors by service, the server's, the offices' AI calls, each AI provider's keys (resting or not, and why — never a key), the mail.
$R = '/home/u791571885/domains/dar.ink/public_html'; require $R . '/dar-sync.php';
$S = strtotime('2026-10-24T08:13:00Z'); $Sms = $S * 1000; $o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
$tq = function (string $sql, array $a = []) { try { return q($sql, $a)->fetchAll(); } catch (Throwable $e) { return 'ERR ' . $e->getMessage(); } };
$pg = []; foreach (@file($R . '/.dar/errors.log') ?: [] as $l) { $x = json_decode($l, true); if (!is_array($x) || strtotime((string)($x['t'] ?? '')) < $S) continue; $k = ($x['app'] ?? '') . '|' . ($x['route'] ?? '') . '|' . mb_substr((string)($x['msg'] ?? ''), 0, 120) . '|' . ($x['where'] ?? ''); $pg[$k] = ($pg[$k] ?? 0) + max(1, (int)($x['count'] ?? 1)); }
arsort($pg); $o['pages'] = array_slice($pg, 0, 50, true);
$se = log_tail(data_dir() . '/server-errors.log', time() - $S); $o['server'] = array_map(fn($g) => [$g['n'], $g['where'], mb_substr($g['msg'], 0, 160)], array_slice($se['groups'], 0, 20));
$o['lrn'] = $tq("SELECT fn, scope, COUNT(*) n, SUM(ok = 0) bad, ROUND(AVG(ms)) ms, MAX(ms) mx, GROUP_CONCAT(DISTINCT provider) pv, GROUP_CONCAT(DISTINCT code) codes FROM lrn_calls WHERE at > ? GROUP BY fn, scope ORDER BY n DESC LIMIT 40", [$Sms]);
$o['ai_codes'] = $tq("SELECT CASE WHEN ns = 'platform' THEN 'p' ELSE 't' END w, provider, code, COUNT(*) n FROM ai_log WHERE at > ? AND ok = 0 GROUP BY w, provider, code ORDER BY n DESC LIMIT 30", [$Sms]);
$cfg = ai_cfg(); $ks = [];
foreach ($cfg['order'] as $id) { if (empty($cfg['providers'][$id]['enabled'])) continue; $sl = ai_slots($cfg, $id); $r = ['n' => count($sl), 'resting' => 0, 'why' => []];
  foreach ($sl as $k) { $st = ai_key_state($cfg, $k); if ((int)($st['rest_until'] ?? 0) > time()) { $r['resting']++; $w = (string)($st['err'] ?? '?') . ' ' . round(((int)$st['rest_until'] - time()) / 60) . 'm'; $r['why'][] = $w; } }
  $ks[$id] = $r; }
$o['keys'] = $ks; $o['web_rest'] = meta_get('ai_web_rest2');
$o['mail'] = $tq("SELECT via, ok, kind, COUNT(*) n, MAX(err) err FROM mail_log WHERE at > ? GROUP BY via, ok, kind", [$Sms]);
$o['mail_s'] = $tq("SELECT via, ok, kind, COUNT(*) n, MAX(err) err FROM mail_log WHERE at > ? GROUP BY via, ok, kind", [$S]);
$o['jobs'] = $tq("SELECT kind, status, COUNT(*) n, MAX(err) err FROM ai_jobs WHERE updated > ? GROUP BY kind, status ORDER BY n DESC LIMIT 20", [$Sms]);
$o['sessions'] = $tq("SELECT u.app, COUNT(DISTINCT u.tenant) t, COUNT(*) n FROM sessions s JOIN users u ON u.id = s.uid WHERE s.created > ? GROUP BY u.app", [$Sms]);
$o['lang'] = ['used_today' => function_exists('lang_bg_used') ? lang_bg_used() : null, 'q' => $tq("SELECT lang, COUNT(*) n, SUM(tries >= 3) f FROM tm_q GROUP BY lang ORDER BY n DESC LIMIT 12")];
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
