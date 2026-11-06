<?php
// A read-only look: is the after-install check still running, and where the market data's first build stands. Nothing changed.
ini_set('display_errors', '1'); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['version' => SYNC_VERSION, 'meta' => []]; foreach (q("SELECT k, substr(v, 1, 400) v FROM meta WHERE k LIKE 'mr_%' OR k IN ('cron_at', 'cron_lock', 'cron_via', 'site_url', 'wd_duty_at')")->fetchAll() as $r) $o['meta'][$r['k']] = $r['v'];
$o['cron_at_utc'] = gmdate('c', (int)($o['meta']['cron_at'] ?? 0)); $o['now'] = gmdate('c');
$o['mr_files'] = array_map(fn($f) => basename($f) . ' ' . filesize($f), glob(mr_dir() . '/*') ?: []); $o['wd_sites'] = (int)(one('SELECT COUNT(*) n FROM wd_sites')['n'] ?? -1);
$o['errors'] = array_slice(array_map(fn($g) => $g['n'] . '× ' . $g['where'] . ' ' . substr($g['msg'], 0, 160), log_tail(data_dir() . '/server-errors.log', 3600)['groups'] ?? []), 0, 8);
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n";
