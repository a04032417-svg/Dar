<?php
// After 3.33.67 (the photo bank): the bank as the minute's cron job fills it (what, from which door), the gallery's and the visitors'
// sites as their stand-ins are replaced, Cloudflare's accounts found per key, then a visitor's new site — how many real photographs
ini_set('display_errors', '1'); error_reporting(E_ALL); @set_time_limit(1500);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION, 'start_bank' => pbank_count()]; $H = 'https://dar.ink';
$post = function (string $u, $body, int $t = 70): array { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $t, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE), CURLOPT_HTTPHEADER => ['Content-Type: application/json']]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s, $b]; };
// 1. eight minutes of the cron job's minutes (it runs the bank's duty by itself)
$t0 = time(); while (time() - $t0 < 480) sleep(20);
$o['bank'] = pbank_count(); $o['by'] = q('SELECT rg, ty, sz, COUNT(*) n FROM pbank GROUP BY rg, ty, sz ORDER BY MIN(id)')->fetchAll();
$o['want'] = o2a(jdec((string)(meta_get('pbank_want') ?? '')));
foreach (ai_keys('cloudflare') as $slot) { $h = substr(md5($slot), 0, 10); $o['cf'][] = ['acc' => (string)(meta_get('pb_cf_acc:' . $h) ?? '') !== '' ? 'found' : '', 'rest_h' => round(max(0, (int)(meta_get('pb_cf_rest:' . $h) ?? 0) - time()) / 3600, 1)]; }
$o['door_next_in'] = (int)(meta_get('pol_open_next') ?? 0) - time();
$lf = data_dir() . '/server-errors.log'; $o['faults'] = is_file($lf) ? array_values(array_filter(array_map('trim', array_slice(file($lf) ?: [], -300)), fn($l) => preg_match('/pbank|webfree|media/', $l) && strcmp(substr((string)(json_decode($l, true)['t'] ?? ''), 0, 19), gmdate('Y-m-d\\TH:i:s', time() - 3600)) > 0)) : [];
// 2. the gallery's examples: photographs and stand-ins left
foreach (ISITE_EX as $x) { $r = o2a(jdec((string)meta_get('gx:' . $x[0]))); $all = json_encode($r['d'] ?? [], JSON_UNESCAPED_UNICODE); $o['examples'][$x[0]] = ['img' => substr_count($all, 'data-ai-img'), 'left' => preg_match_all('/\{\{\s*img\b/', $all)]; }
// 3. a visitor's new website
$t1 = microtime(true);
[$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.site', 'payload' => ['name' => 'مكتب الريان العقاري', 'city' => 'الرياض', 'kind' => 'office', 'about' => 'بيع الفلل والشقق والأراضي في شرق الرياض وإدارة الأملاك للمستثمرين، مع متابعة شخصية حتى نقل الملكية'], 'app' => 'site']);
$id = (string)(json_decode($b, true)['data']['id'] ?? ''); $st = ''; $stages = [];
if ($id !== '') { for ($i = 0; $i < 60; $i++) { sleep(3); [$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.site_status', 'payload' => ['id' => $id], 'app' => 'site']); $jj = json_decode($b, true)['data'] ?? []; $st = (string)($jj['status'] ?? 'x'); $sg = (string)($jj['stage'] ?? ''); if ($sg !== '' && !in_array($sg, array_map(fn($x) => explode('@', $x)[0], $stages), true)) $stages[] = $sg . '@' . round(microtime(true) - $t1) . 's'; if (in_array($st, ['ready', 'failed'], true)) break; } }
$rec = o2a(jdec((string)meta_get('gs:' . $id))); $all = json_encode($rec['d'] ?? [], JSON_UNESCAPED_UNICODE);
$o['site'] = ['id' => $id, 'status' => $st, 'stages' => $stages, 'secs' => round(microtime(true) - $t1), 'by' => $rec['by'] ?? '', 'img' => substr_count($all, 'data-ai-img'), 'left' => preg_match_all('/\{\{\s*img\b/', $all), 'url' => $H . '/free/site/' . $id . '/'];
$o['end_bank'] = pbank_count();
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
