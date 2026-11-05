<?php
// After 3.33.75: the setup-call link off the platform's record; a visitor's new website made live with its photographs (made for it
// alone); two of them, small, to look at; the gallery's examples made again with photographs
ini_set('display_errors', '0'); @set_time_limit(1300);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION]; $H = 'https://dar.ink';
art_nocall(); $o['book'] = (string)(platform_rec()->landing->company->book ?? '');
$post = function (string $u, $body, int $t = 70): array { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $t, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE), CURLOPT_HTTPHEADER => ['Content-Type: application/json']]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s, $b]; };
$t1 = microtime(true);
[$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.site', 'payload' => ['name' => 'مكتب الياسمين العقاري', 'city' => 'الرياض', 'kind' => 'office', 'about' => 'بيع وتأجير الفلل والشقق والأراضي في شمال الرياض: الياسمين والنرجس والملقا، وإدارة الأملاك بخدمة شخصية'], 'app' => 'site']);
$id = (string)(json_decode($b, true)['data']['id'] ?? ''); $st = ''; $stages = [];
if ($id !== '') { for ($i = 0; $i < 260; $i++) { sleep(3); [$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.site_status', 'payload' => ['id' => $id], 'app' => 'site']); $jj = json_decode($b, true)['data'] ?? []; $st = (string)($jj['status'] ?? 'x'); $sg = (string)($jj['stage'] ?? ''); if ($sg !== '' && (!$stages || explode('@', end($stages))[0] !== $sg)) $stages[] = $sg . '@' . round(microtime(true) - $t1) . 's'; if (in_array($st, ['ready', 'failed'], true)) break; } }
$rec = o2a(jdec((string)meta_get('gs:' . $id))); $all = json_encode($rec['d'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); preg_match_all('/dar-sync\.php\?f=([0-9a-f]{48})/', $all, $fm);
$o['site'] = ['status' => $st, 'stages' => $stages, 'secs' => round(microtime(true) - $t1), 'photos' => count(array_unique($fm[1])), 'left' => preg_match_all('/\{\{\s*img\b/', $all), 'url' => $H . '/free/site/' . $id . '/'];
foreach (array_slice(array_values(array_unique($fm[1])), 0, 4) as $x) { $bb = @file_get_contents(file_path($x)); if ($bb && ($im = @imagecreatefromstring($bb))) { $w = imagesx($im); $h = imagesy($im); $nw = 520; $nh = (int)round($h * $nw / max(1, $w)); $t = imagecreatetruecolor($nw, $nh); imagecopyresampled($t, $im, 0, 0, 0, 0, $nw, $nh, $w, $h); ob_start(); imagejpeg($t, null, 80); $o['thumbs'][] = [$w . 'x' . $h, base64_encode((string)ob_get_clean())]; } }
$o['pad'] = str_repeat('x', 90000);
echo json_encode($o, JSON_UNESCAPED_SLASHES), "\n";
