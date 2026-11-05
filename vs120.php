<?php
// After 3.33.74 (the illustrator's second pass): one picture drawn as a site's (time, size, shapes — kept, to look at), then a visitor's
// new website made live with its pictures
ini_set('display_errors', '0'); @set_time_limit(1300);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION]; $c = grow_owner(); $H = 'https://dar.ink';
$look = ['brand' => '#1f3a2e', 'accent' => '#c8a24a', 'bg' => '#f7f3ec', 'ink' => '#1a1a1a', 'mode' => 'light', 'arch' => 'contemporary Najdi villas, Riyadh'];
$t = microtime(true); $svg = art_draw($c, 'contemporary Najdi villa facade with palm trees at golden hour, north Riyadh', 'wide', $look, 55);
$o['draw'] = ['s' => round(microtime(true) - $t, 1), 'len' => strlen($svg), 'shapes' => preg_match_all('/<(path|rect|circle|ellipse|polygon|polyline|line|use)\b/i', $svg)]; $o['svgs'][] = base64_encode($svg);
$post = function (string $u, $body, int $t = 70): array { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $t, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE), CURLOPT_HTTPHEADER => ['Content-Type: application/json']]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s, $b]; };
$t1 = microtime(true);
[$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.site', 'payload' => ['name' => 'مكتب الندى العقاري', 'city' => 'جدة', 'kind' => 'office', 'about' => 'بيع وتأجير الشقق والفلل في شمال جدة: أبحر والشاطئ والمحمدية، وإدارة الأملاك بخدمة شخصية'], 'app' => 'site']);
$id = (string)(json_decode($b, true)['data']['id'] ?? ''); $st = ''; $stages = [];
if ($id !== '') { for ($i = 0; $i < 260; $i++) { sleep(3); [$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.site_status', 'payload' => ['id' => $id], 'app' => 'site']); $jj = json_decode($b, true)['data'] ?? []; $st = (string)($jj['status'] ?? 'x'); $sg = (string)($jj['stage'] ?? ''); if ($sg !== '' && (!$stages || explode('@', end($stages))[0] !== $sg)) $stages[] = $sg . '@' . round(microtime(true) - $t1) . 's'; if (in_array($st, ['ready', 'failed'], true)) break; } }
$rec = o2a(jdec((string)meta_get('gs:' . $id))); $all = json_encode($rec['d'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); preg_match_all('/dar-sync\.php\?art=([0-9a-f]{32})/', $all, $am);
$o['site'] = ['status' => $st, 'stages' => $stages, 'secs' => round(microtime(true) - $t1), 'drawn' => count(array_unique($am[1])), 'left' => preg_match_all('/\{\{\s*img\b/', $all), 'url' => $H . '/free/site/' . $id . '/'];
foreach (array_slice(array_values(array_unique($am[1])), 0, 4) as $aid) { $r = one('SELECT svg FROM art WHERE id = ?', [$aid]); if ($r) $o['svgs'][] = base64_encode((string)$r['svg']); }
$left = 0; foreach (q("SELECT v FROM meta WHERE k LIKE 'gx:%'")->fetchAll() as $row) $left += preg_match_all('/\{\{\s*img\b/', (string)$row['v']); $o['gallery_left'] = $left;
$o['pad'] = str_repeat('x', 90000);
echo json_encode($o, JSON_UNESCAPED_SLASHES), "\n";
