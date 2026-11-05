<?php
// Where the free AI allowance went in the last 24 hours (calls by function and by provider, answered or not) — counts only
ini_set('display_errors', '0'); @set_time_limit(120);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$since = now_ms() - 86400000; $o = ['ver' => SYNC_VERSION];
$o['by_provider'] = q('SELECT provider, SUM(ok) ok, COUNT(*) n FROM ai_log WHERE at > ? GROUP BY provider ORDER BY n DESC', [$since])->fetchAll();
try { $o['by_fn'] = q('SELECT fn, COUNT(*) n, SUM(CASE WHEN ok = 1 THEN 1 ELSE 0 END) ok FROM lrn_calls WHERE at > ? GROUP BY fn ORDER BY n DESC LIMIT 25', [$since])->fetchAll(); } catch (Throwable $e) { $o['by_fn'] = 'err ' . substr($e->getMessage(), 0, 120); }
try { $cols = array_column(q('PRAGMA table_info(lrn_calls)')->fetchAll(), 'name'); $o['lrn_cols'] = $cols; } catch (Throwable $e) { }
$o['gemini_by_hour'] = q("SELECT strftime('%H', at / 1000, 'unixepoch') h, COUNT(*) n, SUM(ok) ok FROM ai_log WHERE at > ? AND provider = 'gemini' GROUP BY h ORDER BY h", [$since])->fetchAll();
$o['hub_new_by_lang'] = q("SELECT lang, COUNT(*) n FROM grow_pages WHERE created > ? GROUP BY lang", [$since])->fetchAll();
echo json_encode($o, JSON_UNESCAPED_SLASHES), "\n";
