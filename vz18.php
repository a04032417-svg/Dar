<?php
// Dar — read-only look at the live site after 3.33.28 (2026-10-28): what the install changed and how the site answers many visitors at once.
// Changes nothing but the cache files the site keeps by itself (the language status, once). Sends 2 rounds of 30 simultaneous first-page reads.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
$s = function (callable $f) { try { return $f(); } catch (Throwable $e) { return 'ERR ' . substr($e->getMessage(), 0, 200); } };
$man = json_decode((string)@file_get_contents($R . '/lib/manifest.json'), true) ?: [];
$o['manifest'] = ['version' => $man['version'] ?? '', 'token' => $man['token'] ?? '', 'built' => $man['built'] ?? ''];
$site = rtrim(site_url(), '/'); $o['site'] = $site;
$head = function (string $url) { $ch = curl_init($url); curl_setopt_array($ch, [CURLOPT_NOBODY => true, CURLOPT_HEADER => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_FOLLOWLOCATION => false]); $h = (string)curl_exec($ch); $c = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    $cc = ''; foreach (explode("\n", $h) as $l) if (stripos($l, 'cache-control:') === 0) $cc = trim(substr($l, 14)); return [$c, $cc]; };
$o['headers'] = $s(fn() => ['versioned_js' => $head($site . '/assets/v/' . ($man['token'] ?? 'x') . '/core.js'), 'plain_js' => $head($site . '/assets/core.js'), 'page' => $head($site . '/app/index.html')]);
$o['lang_cache'] = $s(function () { $f = data_dir() . '/langstatus.json'; return is_file($f) ? ['bytes' => filesize($f), 'age_s' => time() - filemtime($f)] : 'none yet'; });
$o['slots'] = $s(function () { $d = data_dir() . '/slots'; return is_dir($d) ? count(glob($d . '/*') ?: []) . ' files' : 'no dir'; });
$o['pragmas'] = $s(function () { $x = []; foreach (['journal_mode', 'wal_autocheckpoint', 'cache_size', 'temp_store', 'mmap_size', 'synchronous'] as $p) $x[$p] = q('PRAGMA ' . $p)->fetchColumn(); return $x; });
$o['whop_now'] = $s(function () { $w = tr_whop(); $r0 = whop_company($w, false); $co = (string)($r0['id'] ?? ''); $m = fn($v) => strlen($v) > 8 ? substr($v, 0, 4) . '…' . substr($v, -3) : $v;
    $use = $co !== '' ? $co : (string)$w['company']; $r = tr_whop_api('GET', '/cards?first=1&account_id=' . rawurlencode($use));
    return ['resolved_company' => $m($co), 'cards_code' => $r['code'], 'cards_why' => whop_2xx($r) ? 'ok ' . count(whop_list($r)) : substr(whop_why($r), 0, 200), 'card_saved' => tr_card()['id'] ?? '', 'card_state' => meta_get('tr_card_state'), 'last_err' => jdec((string)(meta_get('whop_last_err') ?? ''))]; });
$o['ai_keys'] = $s(function () { $cfg = ai_cfg(); $x = []; foreach ($cfg['order'] as $id) { $sl = ai_slots($cfg, $id); $st = []; foreach ($sl as $k) { $a = ai_key_state($cfg, $k); $st[] = [substr($k, -3), max(0, (int)($a['rest_until'] ?? 0) - time()), substr((string)($a['err'] ?? ''), 0, 60)]; } $x[$id] = $st; } return $x; });
$o['refused_2d'] = $s(function () { $x = []; foreach (q('SELECT app, op, code, SUM(n) n FROM op_refused WHERE day >= ? GROUP BY app, op, code ORDER BY n DESC LIMIT 12', [gmdate('Y-m-d', time() - 86400)])->fetchAll() as $r) $x[] = [$r['app'], $r['op'], $r['code'], (int)$r['n']]; return $x; });
$o['doc_open'] = $s(function () { doc_db(); $x = []; foreach (q("SELECT code, level, status FROM doc_inc WHERE status IN ('open','waiting','stuck') ORDER BY id DESC LIMIT 10")->fetchAll() as $r) $x[] = [$r['code'], $r['level'], $r['status']]; return $x; });
// many visitors at once: the first read of a page (what every new visitor asks), 30 at the same moment, twice
$load = function (int $n) use ($site) {
    $mh = curl_multi_init(); $hs = []; $t0 = microtime(true); $body = json_encode(['op' => 'sync.pull', 'payload' => ['since' => 0, 'app' => 'app'], 'token' => null, 'app' => 'app', 'lang' => 'ar']);
    for ($i = 0; $i < $n; $i++) { $ch = curl_init($site . '/dar-sync.php'); curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_HTTPHEADER => ['Content-Type: application/json']]); curl_multi_add_handle($mh, $ch); $hs[] = $ch; }
    do { $st = curl_multi_exec($mh, $act); if ($act) curl_multi_select($mh, 0.5); } while ($act && $st === CURLM_OK);
    $ms = []; $ok = 0; $codes = []; foreach ($hs as $ch) { $c = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $codes[$c] = ($codes[$c] ?? 0) + 1; $j = json_decode((string)curl_multi_getcontent($ch), true); if ($c === 200 && !empty($j['ok'])) $ok++; $ms[] = (int)round(curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000); curl_multi_remove_handle($mh, $ch); curl_close($ch); }
    curl_multi_close($mh); sort($ms); $w = microtime(true) - $t0;
    return ['n' => $n, 'ok' => $ok, 'codes' => $codes, 'wall_s' => round($w, 2), 'per_s' => round($n / max(0.01, $w), 1), 'ms_p50' => $ms[(int)(count($ms) * 0.5)] ?? 0, 'ms_p95' => $ms[(int)(count($ms) * 0.95)] ?? 0, 'ms_max' => end($ms) ?: 0];
};
$o['load_1'] = $s(fn() => $load(30)); $o['load_2'] = $s(fn() => $load(30));
$o['health'] = $s(fn() => json_decode((string)@file_get_contents($R . '/.dar/health.json'), true));
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
