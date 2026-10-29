<?php
// Dar — read-only look: how each AI provider's models answered in the last day (counts by model and by error code; no keys, no texts, no person's data).
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
try { $cfg = ai_cfg(); $o['ready'] = array_values(ai_ready($cfg)); } catch (Throwable $e) { $o['cfg_err'] = substr($e->getMessage(), 0, 120); }
$rows = [];
foreach (q('SELECT provider, model, ok, code, COUNT(*) c, MAX(at) last_at FROM ai_log WHERE at > ? GROUP BY provider, model, ok, code ORDER BY provider, model, ok DESC, c DESC', [now_ms() - 86400000])->fetchAll() as $r)
    $rows[] = [$r['provider'], substr((string)$r['model'], 0, 48), (int)$r['ok'], (string)$r['code'], (int)$r['c'], gmdate('H:i', (int)($r['last_at'] / 1000))];
$o['by_model'] = $rows;
$st = []; foreach (q('SELECT provider, SUM(ok) ok, COUNT(*) n FROM ai_log WHERE at > ? GROUP BY provider ORDER BY provider', [now_ms() - 86400000])->fetchAll() as $r) $st[$r['provider']] = [(int)$r['ok'], (int)$r['n']];
$o['by_provider'] = $st;
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
