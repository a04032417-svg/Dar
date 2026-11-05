<?php
// After 3.33.68 (the photo bank with AI Horde): seven minutes of the cron job's minutes, then the bank (what came from where), Horde's
// photographs in flight, the gallery's and visitors' stand-ins left, a visitor's new site, and small copies of a few bank photographs
ini_set('display_errors', '1'); error_reporting(E_ALL); @set_time_limit(1500);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION, 'start_bank' => pbank_count()]; $H = 'https://dar.ink';
$post = function (string $u, $body, int $t = 70): array { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $t, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE), CURLOPT_HTTPHEADER => ['Content-Type: application/json']]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s, $b]; };
$t0 = time(); while (time() - $t0 < 420) sleep(20);
$o['bank'] = pbank_count(); $o['by_rg'] = q('SELECT rg, COUNT(*) n FROM pbank GROUP BY rg')->fetchAll(); $o['new'] = q("SELECT ty, rg, sz, substr(url, -6) u FROM pbank WHERE at > ? ORDER BY id", [($t0 - 60) * 1000])->fetchAll();
$o['horde'] = ['flying' => count(o2a(jdec((string)(meta_get('pbank_hd') ?? ''))) ?: []), 'rest' => max(0, (int)(meta_get('pbank_hd_rest') ?? 0) - time())];
$o['want'] = array_slice(o2a(jdec((string)(meta_get('pbank_want') ?? ''))) ?: [], 0, 12);
$lf = data_dir() . '/server-errors.log'; $o['faults'] = is_file($lf) ? array_values(array_slice(array_filter(array_map('trim', array_slice(file($lf) ?: [], -300)), fn($l) => preg_match('/pbank|webfree|media/', $l)), -4)) : [];
foreach (ISITE_EX as $x) { $r = o2a(jdec((string)meta_get('gx:' . $x[0]))); $all = json_encode($r['d'] ?? [], JSON_UNESCAPED_UNICODE); $o['examples'][$x[0]] = substr_count($all, 'data-ai-img') . '/' . preg_match_all('/\{\{\s*img\b/', $all); }
$t1 = microtime(true);
[$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.site', 'payload' => ['name' => 'مكتب السنابل العقاري', 'city' => 'الرياض', 'kind' => 'office', 'about' => 'بيع وتأجير الشقق والفلل والأراضي في شمال وشرق الرياض، وإدارة العمائر السكنية للمستثمرين'], 'app' => 'site']);
$id = (string)(json_decode($b, true)['data']['id'] ?? ''); $st = '';
if ($id !== '') { for ($i = 0; $i < 60; $i++) { sleep(3); [$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.site_status', 'payload' => ['id' => $id], 'app' => 'site']); $st = (string)((json_decode($b, true)['data'] ?? [])['status'] ?? 'x'); if (in_array($st, ['ready', 'failed'], true)) break; } }
$rec = o2a(jdec((string)meta_get('gs:' . $id))); $all = json_encode($rec['d'] ?? [], JSON_UNESCAPED_UNICODE);
$o['site'] = ['status' => $st, 'secs' => round(microtime(true) - $t1), 'img' => substr_count($all, 'data-ai-img'), 'left' => preg_match_all('/\{\{\s*img\b/', $all), 'url' => $H . '/free/site/' . $id . '/'];
// small copies of the newest bank photographs (to look at them)
foreach (q("SELECT ty, rg, sz, url FROM pbank ORDER BY id DESC LIMIT 4")->fetchAll() as $r) {
    if (!preg_match('/f=([0-9a-f]{48})/', $r['url'], $m)) continue; $b = @file_get_contents(file_path($m[1])); if (!$b || !($im = @imagecreatefromstring($b))) continue;
    $w = imagesx($im); $h = imagesy($im); $nw = 300; $nh = (int)round($h * $nw / max(1, $w)); $t = imagecreatetruecolor($nw, $nh); imagecopyresampled($t, $im, 0, 0, 0, 0, $nw, $nh, $w, $h); ob_start(); imagejpeg($t, null, 72); $o['thumbs'][] = [$r['ty'] . '/' . $r['rg'] . '/' . $r['sz'] . ' ' . $w . 'x' . $h, base64_encode((string)ob_get_clean())];
}
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
