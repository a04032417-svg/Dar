<?php
ini_set('display_errors', '1'); error_reporting(E_ALL);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION, 'ini' => ['max_execution_time' => ini_get('max_execution_time'), 'sapi' => PHP_SAPI]];
foreach (q("SELECT k, v FROM meta WHERE k LIKE 'gs:%'")->fetchAll() as $r) { $x = o2a(jdec((string)$r['v'])); $o['gs'][$r['k']] = ['n' => $x['n'] ?? '', 'st' => $x['st'] ?? '', 'stage' => $x['stage'] ?? '', 'err' => $x['err'] ?? '', 'age_s' => intdiv(now_ms() - (int)($x['at'] ?? 0), 1000), 'by' => $x['by'] ?? '', 'has_d' => is_array($x['d'] ?? null)]; }
$o['errs'] = array_slice(array_map(fn($x) => substr($x, 0, 300), @file(data_dir() . '/server-errors.log', FILE_IGNORE_NEW_LINES) ?: []), -8);
$o['faults'] = []; try { foreach (q("SELECT * FROM meta WHERE k LIKE 'fault%' ORDER BY k DESC LIMIT 3")->fetchAll() as $r) $o['faults'][] = substr((string)$r['v'], 0, 300); } catch (Throwable $e) {}
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
