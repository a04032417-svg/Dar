<?php
// Dar — read-only: who used the AI in the last day and a half (each function's calls, tokens, time, which providers answered) — to see
// whether the platform's own background work (translation, the engineer) leaves the free providers spent for the clients' services
require '/home/u791571885/domains/dar.ink/public_html/dar-sync.php';
$S = (time() - 36 * 3600) * 1000; $tq = function (string $sql, array $a = []) { try { return q($sql, $a)->fetchAll(); } catch (Throwable $e) { return 'ERR ' . $e->getMessage(); } };
$o = [];
$o['by_fn'] = $tq("SELECT fn, scope, COUNT(*) n, SUM(tk) tk, ROUND(AVG(ms)) ms, MAX(ms) mx, SUM(ok = 0) bad FROM lrn_calls WHERE at > ? GROUP BY fn, scope ORDER BY tk DESC LIMIT 25", [$S]);
$o['by_fn_provider'] = $tq("SELECT fn, provider, COUNT(*) n, SUM(tk) tk, ROUND(AVG(ms)) ms FROM lrn_calls WHERE at > ? AND ok = 1 GROUP BY fn, provider ORDER BY n DESC LIMIT 40", [$S]);
$o['by_hour'] = $tq("SELECT CAST((at / 3600000) % 24 AS INTEGER) h, COUNT(*) n, SUM(tk) tk FROM lrn_calls WHERE at > ? GROUP BY h ORDER BY h", [$S]);
$o['ai_log_ns'] = $tq("SELECT CASE WHEN ns = 'platform' THEN 'platform' ELSE 'tenants' END w, provider, SUM(ok) ok, SUM(ok = 0) bad FROM ai_log WHERE at > ? GROUP BY w, provider ORDER BY w, bad DESC", [$S]);
$cfg = ai_cfg(); $o['order'] = $cfg['order'] ?? null; $o['prefer'] = $cfg['prefer'] ?? ($cfg['assist']['prefer'] ?? null); $o['tiers'] = [];
foreach (['economy', 'standard', 'deep'] as $t) foreach (['anthropic', 'groq', 'gemini', 'cerebras', 'openrouter'] as $id) $o['tiers'][$t][$id] = ai_model($cfg, $id, $t);
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
