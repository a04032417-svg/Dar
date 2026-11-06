<?php
// After 3.33.100 (again — the first look's answer was lost to one invalid character): the live site checked from inside — its version; the website assistant made for Dar's own site and asked one question the
// way a visitor's page would (then removed: nothing of it stays); the new pages answered over the internet; where the market data stands.
ini_set('display_errors', '1'); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['version' => SYNC_VERSION, 'site_url' => meta_get('site_url'), 'wa' => function_exists('wd_dar_wa') && wd_dar_wa() !== ''];
try {
  $t = microtime(true); $w = op_wd_make(['name' => 'Dar', 'url' => 'https://dar.ink/', 'lang' => 'en']); $o['wd_make_ms'] = (int)((microtime(true) - $t) * 1000);
  $o['wd'] = ['status' => $w['status'], 'code' => (bool)preg_match('/^DARW-/', $w['code'] ?? ''), 'wa_to_dar' => str_starts_with($w['wa'] ?? '', 'https://wa.me/'), 'know' => mb_substr($w['know'], 0, 300), 'know_len' => mb_strlen($w['know'])];
  $_SERVER['HTTP_ORIGIN'] = rtrim(site_url(), '/'); $t = microtime(true); $r = wd_chat(['id' => $w['id'], 'v' => 'probe00000001', 'm' => 'What does Dar do for a real-estate agency, and how much is it?', 'u' => site_url() . '/free/assistant/']); $o['chat_ms'] = (int)((microtime(true) - $t) * 1000); $o['chat'] = $r;
  $t = microtime(true); $r2 = wd_chat(['id' => $w['id'], 'v' => 'probe00000001', 'm' => 'مرحبا، هل تتكلم العربية؟']); $o['chat2_ms'] = (int)((microtime(true) - $t) * 1000); $o['chat2'] = $r2;
  q('DELETE FROM wd_chats WHERE sid = ?', [$w['id']]); q('DELETE FROM wd_sites WHERE id = ?', [$w['id']]); $o['removed'] = !wd_get($w['id']);
} catch (Throwable $e) { $o['wd_error'] = ($e instanceof DarFail ? $e->darCode . ' ' : '') . substr($e->getMessage(), 0, 200); }
$get = function (string $u, string $m = 'GET') { $ch = curl_init($u); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $m, CURLOPT_TIMEOUT => 25, CURLOPT_HEADER => true, CURLOPT_HTTPHEADER => ['Origin: https://example.com']]); $b = (string)curl_exec($ch); $st = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); curl_close($ch); return $st . ' ' . strlen($b) . (stripos($b, 'access-control-allow-origin: *') !== false ? ' cors' : '') . (preg_match('#<title>([^<]{0,90})#', $b, $m2) ? ' «' . $m2[1] . '»' : ''); };
foreach (['https://dar.ink/w.js', 'https://dar.ink/free/assistant/', 'https://dar.ink/free/report/', 'https://dar.ink/market/', 'https://dar.ink/market/gb/', 'https://dar.ink/sitemap.xml'] as $u) $o['http'][$u] = $get($u);
$o['http']['OPTIONS wq'] = $get('https://dar.ink/dar-sync.php?wq=1', 'OPTIONS');
$o['areas'] = ['gb' => array_map(fn($r) => $r['kind'] . ' ' . $r['n'], q("SELECT kind, COUNT(*) n FROM mr_areas WHERE cc = 'gb' GROUP BY kind")->fetchAll()), 'fr' => array_map(fn($r) => $r['kind'] . ' ' . $r['n'], q("SELECT kind, COUNT(*) n FROM mr_areas WHERE cc = 'fr' GROUP BY kind")->fetchAll())];
$top = one("SELECT slug, name, n, d FROM mr_areas WHERE cc = 'gb' AND kind = 'la' ORDER BY n DESC LIMIT 1"); $d = json_decode($top['d'], true); $o['gb_top'] = [$top['name'], $top['n'], $d['med'], $d['chg']]; $o['http']['gb_top'] = $get(mr_url('gb', $top['slug']));
$pa = mr_area_by('fr', 'c:75056'); $o['paris'] = $pa ? [$pa['n'], $pa['d']['apt']['m2'], $pa['d']['mai']['m2'], $pa['d']['chg_apt']] : null; $o['http']['paris'] = $get(mr_url('fr', '75/75056-paris')); $o['http']['ar'] = $get(mr_url('gb', $top['slug'], 'ar'));
$sm = (string)@file_get_contents('https://dar.ink/sitemap.xml'); $o['sitemap_market_urls'] = substr_count($sm, '/market/');
$ix = http_out('POST', 'https://api.indexnow.org/indexnow', ['Content-Type: application/json; charset=utf-8'], jenc(['host' => 'dar.ink', 'key' => grow_ix_key(), 'keyLocation' => site_url() . '/dar-sync.php?indexnow=1', 'urlList' => [site_url() . '/market/', site_url() . '/free/assistant/', site_url() . '/free/report/']]), 20); $o['indexnow_try'] = [(int)$ix[0], substr((string)($ix[1] ?? ''), 0, 120)];
$o['market'] = mr_summary(); $o['mr_state'] = ['gb' => mr_state('gb'), 'fr' => array_diff_key(mr_state('fr'), ['deps' => 1, 'done' => 1]) + ['done' => count(mr_state('fr')['done'] ?? [])]];
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR), "\n";
