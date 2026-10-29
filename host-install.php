<?php
// Dar v3.32 — the host's own installer of a release, run ONCE from the command line by a cron job the deployer creates and deletes
// (the owner, 2026-10-21: «ثبّتها أنت بعد الاختبار»). It installs as the panel's «تحديث الموقع» does, with the code the site runs now:
// the package read and checked by the live site's own functions (Dar's files only, the copy's own edition, every PHP file parses,
// the guard reads it as the site will), the whole site copied to a backup in Dar's own backups folder FIRST, the files put in place,
// the build's fingerprints kept — then the web server made to read the new code (its PHP cache is not the command line's: a one-shot
// file on the site resets it, and the site is asked until it answers with the new version), then the site asked from outside (the
// guard's canary). Anything wrong at any step after the backup — the files not all written, the web still on the old code, the
// canary failing — and the backup is put back at once, every file of it (whatever its name), and the files only the new version
// brought are taken away, so the site is exactly what it was. The data folder is never touched; the site's own config.json keeps its
// values (the package only adds the keys the site does not have yet).
// The package travels encrypted (AES-256-GCM); its key and its SHA-256 are given on the command line, never stored or printed.
//   php host-install.php audit   <site_root>                        → what the live site is (version, files changed since its build)
//   php host-install.php install <site_root> <key-base64> <sha256>   → the install; prints one JSON line
//   php host-install.php restore <site_root> <backup-name>           → puts a backup of Dar's backups folder back (the way back)
// A job runs once: run again, it says what it did (with that run's outcome); a second job while one works says «busy».
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
@set_time_limit(0); @ini_set('memory_limit', '512M');
function hi_out(array $r, int $code = 0): void { echo json_encode($r, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n"; exit($code); }
$mode = (string)($argv[1] ?? ''); $root = rtrim((string)($argv[2] ?? ''), '/'); $arg3 = (string)($argv[3] ?? ''); $arg4 = (string)($argv[4] ?? ''); // (read now: $argv is handed to the site's code below)
$test = getenv('DAR_INSTALL_TEST') === '1';
if (!in_array($mode, ['audit', 'install', 'restore'], true)) hi_out(['ok' => false, 'error' => 'usage'], 2);
if (!$test && !preg_match('#^/home/[a-z0-9_]+/domains/[a-z0-9.-]+/public_html$#', $root)) hi_out(['ok' => false, 'error' => 'root'], 2);
if (!is_file($root . '/dar-sync.php') || !is_file($root . '/assets/core.js')) hi_out(['ok' => false, 'error' => 'not_dar'], 2);
$here = __DIR__; $done = $here . '/done-' . $mode . '.json';
// one run at a time (a cron job that fires again while the first run still works waits for nothing: it says «busy» and stops)
$lock = @fopen($here . '/run.lock', 'c'); if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) hi_out(['ok' => false, 'error' => 'busy'], 1);
if ($mode !== 'audit' && is_file($done)) { $prev = json_decode((string)file_get_contents($done), true) ?: []; hi_out(['ok' => !empty($prev['ok']), 'again' => $prev], !empty($prev['ok']) ? 0 : 1); }

// the package, decrypted and checked BEFORE anything of the site is loaded
$zipBytes = null;
if ($mode === 'install') {
    $key = base64_decode($arg3, true); $want = strtolower($arg4);
    if ($key === false || strlen($key) !== 32 || !preg_match('/^[a-f0-9]{64}$/', $want)) hi_out(['ok' => false, 'error' => 'args'], 2);
    $blob = @file_get_contents($here . '/dar.zip.enc'); if ($blob === false || strlen($blob) < 1000) hi_out(['ok' => false, 'error' => 'blob'], 1);
    $zipBytes = openssl_decrypt(substr($blob, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($blob, 0, 12), substr($blob, 12, 16)); unset($blob, $key);
    if ($zipBytes === false || !hash_equals($want, hash('sha256', $zipBytes))) hi_out(['ok' => false, 'error' => 'integrity'], 1);
}

// ---- the installer's own means (none of them needs the site's code)
// the site, asked over the web as a visitor asks it
function hi_http(string $url, int $timeout = 20): array
{
    if (!function_exists('curl_init')) return [0, ''];
    $ch = curl_init($url); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => $timeout, CURLOPT_FOLLOWLOCATION => false, CURLOPT_HTTPHEADER => ['Cache-Control: no-cache', 'Accept: application/json, text/plain, */*'], CURLOPT_USERAGENT => 'Dar host installer']);
    $b = curl_exec($ch); $c = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE); curl_close($ch); return [$c, is_string($b) ? $b : ''];
}
function hi_server_version(string $base): string { [$c, $b] = hi_http($base . '/dar-sync.php?op=health&_=' . bin2hex(random_bytes(4)), 15); $j = json_decode($b, true); return $c === 200 && is_array($j) ? (string)($j['data']['server'] ?? '') : ''; }
// the web server keeps compiled PHP in its own cache (the command line's reset is not its): a file of one use, at a name nobody can
// guess, asks it to forget — and removes itself first
function hi_web_reset(string $root, string $base): string
{
    $name = 'dar-opc-' . bin2hex(random_bytes(16)) . '.php'; $f = $root . '/' . $name;
    if (@file_put_contents($f, "<?php @unlink(__FILE__); header('Cache-Control: no-store'); echo function_exists('opcache_reset') ? (opcache_reset() ? 'reset' : 'refused') : 'none';\n") === false) return 'unwritable';
    @chmod($f, 0644); [$c, $b] = hi_http($base . '/' . $name, 15); if (is_file($f)) @unlink($f);
    return $c === 200 ? substr(trim($b), 0, 20) : 'http_' . $c;
}
// until the web answers with this version (asked every second; its cache reset again every ten seconds)
function hi_wait_version(string $root, string $base, string $want, int $secs): array
{
    $t0 = time(); $resets = []; $seen = ''; $next = $t0;
    while (true) {
        if (time() >= $next) { $resets[] = hi_web_reset($root, $base); $next = time() + 10; }
        $seen = hi_server_version($base); if ($seen === $want) return ['ok' => true, 'seen' => $seen, 'after_s' => time() - $t0, 'resets' => $resets];
        if (time() - $t0 >= $secs) return ['ok' => false, 'seen' => $seen, 'after_s' => time() - $t0, 'resets' => $resets];
        sleep(1);
    }
}
function hi_sync_version(string $php): string { return preg_match("/const SYNC_VERSION = '([^']{1,40})'/", $php, $m) ? $m[1] : ''; }
// every file of a backup put back exactly (whatever its name — the site's own reader refuses some names a person may give a file),
// and the files the update alone brought taken away
function hi_restore_all(string $zipPath, string $root, array $added): array
{
    $z = new ZipArchive(); if ($z->open($zipPath) !== true) return ['ok' => false, 'why' => 'zip'];
    $n = 0; $bad = []; $have = [];
    try {
        for ($i = 0; $i < $z->numFiles; $i++) {
            $st = $z->statIndex($i); $name = (string)($st['name'] ?? ''); if ($name === '' || substr($name, -1) === '/') continue;
            if ($name[0] === '/' || strpos($name, '..') !== false || strpbrk($name, "\\\0") !== false) { $bad[] = $name; continue; }
            $have[$name] = true; $dest = $root . '/' . $name; $d = dirname($dest);
            if (!is_dir($d) && !@mkdir($d, 0755, true)) { $bad[] = $name; continue; }
            $data = $z->getFromIndex($i); if ($data === false) { $bad[] = $name; continue; }
            $tmp = $dest . '.dar-new'; if (@file_put_contents($tmp, $data) === false || !@rename($tmp, $dest)) { @unlink($tmp); $bad[] = $name; continue; }
            $n++;
        }
    } finally { $z->close(); }
    $gone = 0; foreach ($added as $rel) { $rel = (string)$rel; if ($rel === '' || isset($have[$rel]) || strpos($rel, '..') !== false) continue; if (is_file($root . '/' . $rel) && @unlink($root . '/' . $rel)) $gone++; }
    if (function_exists('opcache_reset')) @opcache_reset();
    return ['ok' => !$bad, 'files' => $n, 'removed' => $gone, 'failed' => array_slice($bad, 0, 10)];
}
function hi_zip_names(ZipArchive $z): array { $o = []; for ($i = 0; $i < $z->numFiles; $i++) { $s = $z->statIndex($i); $n = (string)($s['name'] ?? ''); if ($n !== '' && substr($n, -1) !== '/') $o[$n] = true; } return $o; }
function hi_write_atomic(string $f, string $s): bool { $t = $f . '.dar-new'; if (@file_put_contents($t, $s) === false) return false; if (!@rename($t, $f)) { @unlink($t); return false; } return true; }

// the LIVE site's own code (its installer, its backups, its guard) — nothing in it runs from the command line except its cron
chdir($root); $argv = [$root . '/dar-sync.php']; $_SERVER['argv'] = $argv; $_SERVER['argc'] = 1;
require $root . '/dar-sync.php';
$was = defined('SYNC_VERSION') ? SYNC_VERSION : '';
$who = ['user' => ['id' => 'host', 'name' => 'Host installer (cron)'], 'row' => ['id' => 'host', 'role' => 'owner']];
$base = function_exists('self_base') ? self_base() : '';

if ($mode === 'audit') {
    // the live files against the fingerprints of the build the site runs (what an update would replace that the owner changed)
    $man = json_decode((string)@file_get_contents($root . '/lib/manifest.json'), true); $files = is_array($man['files'] ?? null) ? $man['files'] : [];
    $norm = function (string $r, string $b): string { if (!preg_match('/\.(html?|m?js|css)$/', $r)) return $b; $b = (string)preg_replace('#assets/v/[A-Za-z0-9._-]{1,60}/#', 'assets/', $b); return (string)preg_replace('/\?v=[A-Za-z0-9._-]{1,80}(?=["\'])/', '', $b); };
    $changed = []; $missing = [];
    foreach ($files as $r => $sha) { $f = $root . '/' . $r; if (!is_file($f)) { $missing[] = $r; continue; } if (sha1($norm((string)$r, (string)file_get_contents($f))) !== $sha) $changed[] = $r; }
    // names the site's own reader of backups refuses (the panel's «استرجاع» would stop on them; this installer's way back does not)
    $odd = []; $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) { $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($root) + 1)); if (preg_match('#^\.dar/#', $rel)) continue; if (!preg_match('#^[\w.@+-]+(/[\w.@+-]+)*$#', $rel)) $odd[] = $rel; if (count($odd) >= 20) break; }
    $bk = glob(data_dir() . '/backups/site-*.zip') ?: []; sort($bk);
    hi_out(['ok' => true, 'version' => $was, 'built' => $man['built'] ?? null, 'token' => $man['token'] ?? null, 'files' => count($files), 'changed' => $changed, 'missing' => array_slice($missing, 0, 40), 'odd_names' => $odd,
        'data_dir_outside_web' => strpos(realpath(data_dir()) ?: data_dir(), realpath($root) ?: $root) !== 0, 'backups' => array_map('basename', array_slice($bk, -5)), 'disk_free_mb' => (int)(@disk_free_space($root) / 1048576),
        'zip' => class_exists('ZipArchive'), 'curl' => function_exists('curl_init'), 'php' => PHP_VERSION, 'base' => $base, 'web_version' => $base !== '' ? hi_server_version($base) : '',
        'config' => (function () use ($root) { $c = json_decode((string)@file_get_contents($root . '/config.json'), true); return is_array($c) ? ['keys' => array_keys($c), 'mode' => (string)($c['mode'] ?? ''), 'waGateway' => (string)($c['waGateway'] ?? '')] : null; })()]);
}

if ($mode === 'restore') {
    $name = basename($arg3); $f = data_dir() . '/backups/' . $name;
    if (!preg_match('/^site-[0-9-]+\.zip$/', $name) || !is_file($f)) hi_out(['ok' => false, 'error' => 'no_backup'], 1);
    $z = new ZipArchive(); if ($z->open($f) !== true) hi_out(['ok' => false, 'error' => 'zip'], 1);
    $inBk = hi_zip_names($z); $wantVer = hi_sync_version((string)$z->getFromName('dar-sync.php')); $z->close();
    // the files of the version that runs now that the backup does not have — brought by the update — go with it
    $man = json_decode((string)@file_get_contents($root . '/lib/manifest.json'), true); $added = array_values(array_filter(array_keys(is_array($man['files'] ?? null) ? $man['files'] : []), fn($r) => !isset($inBk[$r])));
    $r = hi_restore_all($f, $root, $added);
    if (function_exists('assets_stamp')) { try { assets_stamp(); } catch (Throwable $e) { /* the files are back */ } }
    $web = $base !== '' && $wantVer !== '' ? hi_wait_version($root, $base, $wantVer, 90) : ['ok' => null];
    try { audit_srv($who, 'site.rollback', 'site:' . $name); } catch (Throwable $e) { /* the files are back */ }
    $out = ['ok' => $r['ok'] && ($web['ok'] ?? null) !== false, 'restored' => $name, 'files' => $r['files'] ?? 0, 'removed' => $r['removed'] ?? 0, 'failed' => $r['failed'] ?? [], 'version' => $wantVer, 'web' => $web];
    @file_put_contents($done, json_encode($out)); hi_out($out, $out['ok'] ? 0 : 1);
}

// ---- install: Dar's own «تحديث الموقع», from the host
$file = ''; $z = null;
$drop = function () use (&$file, &$z): void { if ($z instanceof ZipArchive) { try { @$z->close(); } catch (Throwable $e) { /* closed */ } $z = null; } if ($file !== '' && is_file($file)) @unlink($file); };
$fin = function (array $r, int $code) use (&$drop, $done): void { $drop(); @file_put_contents($done, json_encode($r)); hi_out($r, $code); };
try {
    if (!class_exists('ZipArchive')) hi_out(['ok' => false, 'error' => 'no_zip'], 1);
    $up = data_dir() . '/updates'; if (!is_dir($up)) @mkdir($up, 0700, true);
    $file = $up . '/upload-' . gmdate('Ymd-His') . '-host.zip'; if (@file_put_contents($file, $zipBytes) === false) { $file = ''; hi_out(['ok' => false, 'error' => 'storage'], 1); } $zipBytes = null;
    $z = new ZipArchive(); if ($z->open($file) !== true) { $z = null; $drop(); hi_out(['ok' => false, 'error' => 'not_zip'], 1); }
    // the package checked by the site's own code — nothing written yet
    $entries = site_entries($z);
    foreach ($entries as $name => $i) { if (strtolower(pathinfo((string)$name, PATHINFO_EXTENSION)) !== 'php') continue; try { token_get_all((string)$z->getFromIndex($i), TOKEN_PARSE); } catch (Throwable $e) { $drop(); hi_out(['ok' => false, 'error' => 'php_syntax', 'file' => $name, 'line' => $e->getLine()], 1); } }
    $ver = isset($entries['assets/core.js']) && preg_match("/VERSION\\s*=\\s*['\"]([^'\"]{1,40})['\"]/", (string)$z->getFromIndex($entries['assets/core.js']), $vm) ? $vm[1] : '';
    $newServer = isset($entries['dar-sync.php']) ? hi_sync_version((string)$z->getFromIndex($entries['dar-sync.php'])) : '';
    if ($newServer === '') { $drop(); hi_out(['ok' => false, 'error' => 'no_version'], 1); }
    if (function_exists('guard_check_zip') && ($bad = guard_check_zip($z, $entries))) { $drop(); hi_out(['ok' => false, 'error' => 'guard', 'detail' => array_slice($bad, 0, 8)], 1); }
    @set_time_limit(0); // (the guard's check gives itself ninety seconds; the backup and the files are not bound by it)
    if ($base === '') { $drop(); hi_out(['ok' => false, 'error' => 'no_address'], 1); }
    $js = []; foreach ($entries as $name => $i) if (preg_match('#^assets/[\w.-]+\.m?js$#', (string)$name) && @file_get_contents($root . '/' . $name) !== $z->getFromIndex($i)) $js[] = (string)$name;
    $cfgLive = json_decode((string)@file_get_contents($root . '/config.json'), true);
    // the backup first — and it is read back before a single file of the site changes
    $backup = site_backup($root); $bkPath = data_dir() . '/backups/' . $backup;
    $bz = new ZipArchive(); $bkOk = $bz->open($bkPath) === true; $inBk = $bkOk ? hi_zip_names($bz) : []; if ($bkOk) $bz->close();
    if (!$bkOk || !isset($inBk['dar-sync.php'], $inBk['assets/core.js'], $inBk['index.html'])) { $drop(); hi_out(['ok' => false, 'error' => 'backup_unreadable', 'backup' => $backup], 1); }
    $added = array_values(array_filter(array_keys($entries), fn($r) => !isset($inBk[$r]))); // (what only the new version has: taken away if it is undone)
    $undo = function (string $why, array $extra) use ($bkPath, $root, $added, $base, $was, $backup, $ver, $fin): void {
        $r = hi_restore_all($bkPath, $root, $added); $web = hi_wait_version($root, $base, $was, 90);
        $again = function_exists('guard_canary') ? guard_canary() : ['ok' => null];
        if (function_exists('guard_log')) { try { guard_log(['kind' => 'undone', 'what' => 'site.update ' . $ver . ' (host): ' . $why, 'fails' => array_slice($extra['fails'] ?? [], 0, 6), 'paths' => [$backup], 'back' => $again['ok'] ?? null]); } catch (Throwable $e) { /* logged below */ } }
        $fin(['ok' => false, 'error' => $why, 'undone' => $backup, 'restored' => $r, 'web' => $web, 'back' => $again['ok'] ?? null, 'version' => $ver, 'from' => $was] + $extra, 1);
    };
    // the files
    try { $n = site_extract($z, $entries, $root); }
    catch (Throwable $e) { $undo('extract_failed', ['why' => $e instanceof DarFail ? $e->darCode . ' ' . json_encode($e->extra) : substr($e->getMessage(), 0, 200)]); }
    $drop();
    // the site's own config.json keeps its values; the package adds only the keys it did not have
    $cfgNew = json_decode((string)@file_get_contents($root . '/config.json'), true); $cfgAdded = [];
    if (is_array($cfgLive) && is_array($cfgNew)) { $cfgAdded = array_keys(array_diff_key($cfgNew, $cfgLive)); $m = $cfgLive + $cfgNew; if ($m !== $cfgNew) hi_write_atomic($root . '/config.json', json_encode($m, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"); }
    if (function_exists('doc_golden_make')) { try { $mj = json_decode((string)@file_get_contents($root . '/lib/manifest.json'), true); if (is_array($mj) && is_array($mj['files'] ?? null)) doc_golden_make($mj); } catch (Throwable $e) { log_fault($e, 'host-install:golden'); } }
    // the web reads the new code (or it is undone): its PHP cache reset, the site asked until it answers with the new version
    $web = hi_wait_version($root, $base, $newServer, 120);
    if (!$web['ok']) $undo('web_still_old', ['web_new' => $web]);
    // the site asked from outside (the guard's canary): any failure — or no answer at all — and it is undone
    $g = function_exists('guard_canary') ? guard_canary() : ['ok' => null, 'why' => 'no_guard'];
    if (($g['ok'] ?? null) !== true) $undo('site_broke', ['fails' => array_slice($g['fails'] ?? [], 0, 8), 'why' => $g['why'] ?? '']);
    $still = hi_server_version($base); if ($still !== $newServer) $undo('web_changed', ['seen' => $still]);
    meta_set('guard_update', jenc(['at' => now_ms(), 'backup' => $backup, 'version' => $ver, 'files' => array_slice($js, 0, 400)]));
    $sw = function_exists('assets_autoswitch') ? assets_autoswitch(true) : [];
    audit_srv($who, 'site.update', 'site:' . $ver . ' (host, from ' . $was . ', backup ' . $backup . ')');
    $r = ['ok' => true, 'from' => $was, 'version' => $ver, 'server' => $newServer, 'files' => $n, 'backup' => $backup, 'added' => count($added), 'web' => $web, 'canary' => ['ok' => $g['ok'] ?? null, 'checked' => $g['checked'] ?? null, 'ms' => $g['ms'] ?? null, 'partial' => !empty($g['partial'])], 'switched' => !empty($sw['switched']), 'js_changed' => count($js), 'config_added' => $cfgAdded];
    @unlink($here . '/dar.zip.enc'); $fin($r, 0);
} catch (Throwable $e) {
    $drop();
    hi_out(['ok' => false, 'error' => 'exception', 'class' => get_class($e), 'code' => $e instanceof DarFail ? $e->darCode : null, 'message' => substr($e->getMessage(), 0, 300), 'extra' => $e instanceof DarFail ? $e->extra : null, 'line' => $e->getLine(), 'file' => basename($e->getFile()), 'backup' => $backup ?? null], 1);
}
