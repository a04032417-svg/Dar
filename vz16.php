<?php
// Dar — read-only look at the live site (2026-10-28, after 3.33.27, second): the alerts and mails that reach the owner, the AI's attempts, what the ops refused,
// the tables' sizes, the PHP/SQLite limits, and how the host answers many requests at once (health only). Changes nothing.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
$s = function (callable $f) { try { return $f(); } catch (Throwable $e) { return 'ERR ' . substr($e->getMessage(), 0, 200); } };
$mask = fn($p) => strlen((string)$p) > 6 ? substr((string)$p, 0, 3) . '…' . substr((string)$p, -4) : (string)$p;
$o['env'] = $s(fn() => ['php' => PHP_VERSION, 'sapi' => PHP_SAPI, 'mem' => ini_get('memory_limit'), 'maxexec' => ini_get('max_execution_time'), 'opcache' => (bool)ini_get('opcache.enable'), 'upload' => ini_get('upload_max_filesize'), 'post' => ini_get('post_max_size'),
    'disabled' => substr((string)ini_get('disable_functions'), 0, 160), 'curl' => function_exists('curl_multi_init'), 'sqlite' => SQLite3::version()['versionString'] ?? '', 'cpu' => (function_exists('sys_getloadavg') ? sys_getloadavg() : null), 'cores' => (int)@shell_exec('nproc')]);
$o['db'] = $s(function () { $d = db(); $f = data_dir() . '/dar.sqlite'; $t = []; foreach ($d->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'")->fetchAll() as $r) { $n = (int)$d->query('SELECT COUNT(*) FROM "' . $r['name'] . '"')->fetchColumn(); if ($n >= 1000) $t[$r['name']] = $n; }
    arsort($t); return ['mb' => round(filesize($f) / 1048576, 1), 'wal_mb' => is_file($f . '-wal') ? round(filesize($f . '-wal') / 1048576, 1) : 0, 'journal' => $d->query('PRAGMA journal_mode')->fetchColumn(), 'busy' => $d->query('PRAGMA busy_timeout')->fetchColumn(), 'sync' => $d->query('PRAGMA synchronous')->fetchColumn(), 'big_tables' => array_slice($t, 0, 14, true)]; });
$o['cron'] = $s(fn() => ['at' => gmdate('c', (int)(meta_get('cron_at') ?? 0)), 'via' => meta_get('cron_via'), 'cli' => gmdate('c', (int)(meta_get('cron_cli_at') ?? 0)), 'lock' => (int)(meta_get('cron_lock') ?? 0)]);
$o['mail_24h'] = $s(function () { $x = []; foreach (q('SELECT kind, ok, COUNT(*) c, MAX(at) last FROM mail_log WHERE at > ? GROUP BY kind, ok ORDER BY c DESC LIMIT 30', [(time() - 86400)])->fetchAll() as $r) $x[] = [$r['kind'], (int)$r['ok'], (int)$r['c'], gmdate('m-d H:i', (int)$r['last'])]; return $x; });
$o['mail_last'] = $s(function () use ($mask) { $x = []; foreach (q('SELECT at, kind, rcpt, ok, via, err FROM mail_log ORDER BY id DESC LIMIT 24')->fetchAll() as $r) $x[] = [gmdate('m-d H:i', (int)$r['at']), $r['kind'], $mask($r['rcpt']), (int)$r['ok'], $r['via'], substr((string)$r['err'], 0, 60)]; return $x; });
$o['alert_state'] = $s(function () { $st = o2a(jdec(meta_get('rq_alert_state'))) ?: []; $x = []; foreach ($st as $sig => $t) $x[] = [substr($sig, 0, 40), gmdate('m-d H:i', (int)$t)]; usort($x, fn($a, $b) => strcmp($b[1], $a[1])); return array_slice($x, 0, 30); });
$o['alert_cfg'] = $s(fn() => ['events' => rq_alert_events(), 'email' => (string)rq_cfg_get('alerts.email', 'on'), 'tg' => (string)rq_cfg_get('alerts.tg_chat', '') !== '', 'ntfy' => (string)rq_cfg_get('alerts.ntfy', '') !== '']);
$o['tr_alerts'] = $s(function () { if (!function_exists('tr_db')) return 'none'; tr_db(); $x = []; foreach (q('SELECT at, kind, level, text, ref FROM tr_alerts ORDER BY id DESC LIMIT 10')->fetchAll() as $r) $x[] = [gmdate('m-d H:i', intdiv((int)$r['at'], 1000)), $r['kind'], $r['level'], substr((string)$r['text'], 0, 120)]; return $x; });
$o['whop'] = $s(function () { $c = whop_cfg(); return ['on' => $c['on'], 'on_set' => $c['on_set'], 'env' => $c['env'], 'key' => $c['key'] !== '', 'secret' => $c['secret'] !== '', 'company' => $c['company'] !== '', 'ready' => whop_ready($c), 'payout_on' => $c['payout_on'], 'card' => function_exists('tr_card') ? (bool)(tr_card()['id'] ?? '') : null]; });
$o['ai_24h'] = $s(function () { $x = []; foreach (q('SELECT provider, ok, code, COUNT(*) c, CAST(AVG(ms) AS INT) ms FROM ai_log WHERE at > ? GROUP BY provider, ok, code ORDER BY provider, ok DESC, c DESC', [(time() - 86400)])->fetchAll() as $r) $x[] = [$r['provider'], (int)$r['ok'], $r['code'], (int)$r['c'], (int)$r['ms']]; return $x; });
$o['ops_refused'] = $s(function () { $x = []; foreach (q('SELECT op, code, SUM(n) n FROM op_refused WHERE day >= ? GROUP BY op, code ORDER BY n DESC LIMIT 25', [gmdate('Y-m-d', time() - 3 * 86400)])->fetchAll() as $r) $x[] = [$r['op'], $r['code'], (int)$r['n']]; return $x; });
$o['hits'] = $s(fn() => (int)one('SELECT COUNT(*) n FROM hits')['n']);
$o['users'] = $s(fn() => ['tenants' => (int)one('SELECT COUNT(*) n FROM tenants')['n'], 'users' => (int)one('SELECT COUNT(*) n FROM users')['n'], 'sessions' => (int)one('SELECT COUNT(*) n FROM sessions WHERE exp > ?', [time()])['n']]);
// how the host answers many requests at once: the health op (light PHP) and the front page (static), 10 / 30 / 60 together
$base = 'https://dar.ink';
$o['load'] = $s(function () use ($base) {
    $out = [];
    foreach (['health' => '/dar-sync.php?op=health&_=', 'page' => '/index.html?_='] as $name => $path) foreach ([10, 30, 60] as $n) {
        $mh = curl_multi_init(); $hs = [];
        for ($i = 0; $i < $n; $i++) { $ch = curl_init($base . $path . bin2hex(random_bytes(4))); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_USERAGENT => 'Dar/probe (own capacity check)', CURLOPT_SSL_VERIFYPEER => true]); curl_multi_add_handle($mh, $ch); $hs[] = $ch; }
        $t0 = microtime(true); $run = 0; do { curl_multi_exec($mh, $run); if ($run) curl_multi_select($mh, 0.3); } while ($run && microtime(true) - $t0 < 30);
        $ms = []; $codes = [];
        foreach ($hs as $ch) { $c = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE); $codes[$c] = ($codes[$c] ?? 0) + 1; $ms[] = (int)round(curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000); curl_multi_remove_handle($mh, $ch); curl_close($ch); }
        curl_multi_close($mh); sort($ms);
        $out[$name . $n] = ['codes' => $codes, 'p50' => $ms[(int)(count($ms) * 0.5)], 'p95' => $ms[min(count($ms) - 1, (int)(count($ms) * 0.95))], 'max' => end($ms), 'wall' => (int)round((microtime(true) - $t0) * 1000)];
        usleep(700000);
    }
    return $out; });
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
