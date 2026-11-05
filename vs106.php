<?php
// Why a visitor's site stayed «working»: its record (stage, tries, error, busy), the last server errors, the AI log of the last minutes
ini_set('display_errors', '1'); error_reporting(E_ALL); @set_time_limit(300);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION, 'now' => now_ms()];
foreach (['gs:mw34fvwcef20ff'] as $k) { $r = o2a(jdec((string)meta_get($k))) ?: []; $sj = $r['sj'] ?? []; $o['rec'][$k] = ['st' => $r['st'] ?? '', 'stage' => $r['stage'] ?? '', 'err' => $r['err'] ?? '', 'busy_ago_s' => isset($r['busy']) && $r['busy'] ? round((now_ms() - (int)$r['busy']) / 1000) : 0, 'at_ago_s' => isset($r['at']) ? round((now_ms() - (int)$r['at']) / 1000) : null, 'step' => $sj['step'] ?? '', 'tries' => $sj['tries'] ?? null, 'made' => $sj['made'] ?? null, 'by' => $r['by'] ?? '', 'keys' => array_keys($r)]; }
foreach (q("SELECT k FROM meta WHERE k LIKE 'gs:%' AND v LIKE '%\"st\":\"working\"%' ORDER BY rowid DESC LIMIT 5")->fetchAll() as $row) { $r = o2a(jdec((string)meta_get($row['k']))) ?: []; $o['working'][$row['k']] = [$r['stage'] ?? '', $r['sj']['step'] ?? '', $r['sj']['tries'] ?? null, isset($r['busy']) && $r['busy'] ? round((now_ms() - (int)$r['busy']) / 1000) : 0, isset($r['at']) ? round((now_ms() - (int)$r['at']) / 1000) : null]; }
$lf = data_dir() . '/server-errors.log'; $o['errors'] = is_file($lf) ? array_slice(array_map('trim', file($lf) ?: []), -12) : [];
$o['ai'] = array_map(fn($r) => implode(' ', [round((now_ms() - (int)$r['at']) / 1000) . 's', $r['provider'], $r['model'], $r['ok'], $r['ms'], $r['code']]), q('SELECT * FROM ai_log WHERE at > ? ORDER BY id DESC LIMIT 25', [now_ms() - 1800000])->fetchAll());
$o['studio_live_ago'] = time() - (int)(meta_get('studio_live') ?? 0); $o['cron_lock_ago'] = time() - (int)(meta_get('cron_lock') ?? 0); $o['cron_cli_ago'] = time() - (int)(meta_get('cron_cli_at') ?? 0);
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
