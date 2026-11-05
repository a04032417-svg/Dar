<?php
// Which models answered the agent in the trials (provider, model, time) — and how the platform ranks its providers for it. Read only.
ini_set('display_errors', '0'); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION];
try { $o['calls'] = q("SELECT at, fn, provider, model, ok, code, ms, tries, json, jmiss FROM lrn_calls WHERE (fn LIKE 'wa.agent%' OR fn = 'brain.lang') AND at > ? ORDER BY at", [now_ms() - 3 * 3600000])->fetchAll(); foreach ($o['calls'] as &$c) $c['at'] = gmdate('H:i:s', intdiv((int)$c['at'], 1000)); unset($c); } catch (Throwable $e) { $o['calls'] = 'x:' . $e->getMessage(); }
try { $cfg = ai_cfg(); $ready = ai_ready($cfg); $o['ready'] = $ready; $o['rank_standard'] = function_exists('ai_rank') ? ai_rank($cfg, $ready, 'standard') : null; $o['fast_order'] = function_exists('ai_fast_order') ? ai_fast_order($ready, ai_recent()) : null;
  foreach ($ready as $id) $o['models'][$id] = ['standard' => ai_model($cfg, $id, 'standard'), 'economy' => ai_model($cfg, $id, 'economy')]; } catch (Throwable $e) { $o['rank'] = 'x:' . $e->getMessage(); }
echo json_encode($o, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
