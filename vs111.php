<?php
// After 3.33.70: a visitor's new website made live — its team's stages, its pictures drawn as code by the AI (how many, how long), its
// cards written by the AI; two of its drawings as they are (to look at them); the gallery's pictures still waiting for the cron job
ini_set('display_errors', '0'); @set_time_limit(1200);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION]; $H = 'https://dar.ink';
$post = function (string $u, $body, int $t = 70): array { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $t, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE), CURLOPT_HTTPHEADER => ['Content-Type: application/json']]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s, $b]; };
$t1 = microtime(true);
[$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.site', 'payload' => ['name' => 'مكتب الفيصلية العقاري', 'city' => 'الرياض', 'kind' => 'office', 'about' => 'بيع وتأجير الفلل والشقق في شمال الرياض: الياسمين والنرجس والملقا، وإدارة الأملاك للملاك، مع متابعة شخصية حتى نقل الملكية'], 'app' => 'site']);
$id = (string)(json_decode($b, true)['data']['id'] ?? ''); $st = ''; $stages = [];
if ($id !== '') { for ($i = 0; $i < 240; $i++) { sleep(3); [$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.site_status', 'payload' => ['id' => $id], 'app' => 'site']); $jj = json_decode($b, true)['data'] ?? []; $st = (string)($jj['status'] ?? 'x'); $sg = (string)($jj['stage'] ?? ''); if ($sg !== '' && (!$stages || explode('@', end($stages))[0] !== $sg)) $stages[] = $sg . '@' . round(microtime(true) - $t1) . 's'; if (in_array($st, ['ready', 'failed'], true)) break; } }
$rec = o2a(jdec((string)meta_get('gs:' . $id))); $all = json_encode($rec['d'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
preg_match_all('/dar-sync\.php\?art=([0-9a-f]{32})/', $all, $am); $css = (string)($rec['d']['settings']['css'] ?? '');
$o['site'] = ['id' => $id, 'status' => $st, 'stages' => $stages, 'secs' => round(microtime(true) - $t1), 'by' => $rec['by'] ?? '', 'drawn' => count(array_unique($am[1])), 'left' => preg_match_all('/\{\{\s*img\b/', $all), 'cards_css' => str_contains($css, "this site's cards") ? strlen(substr($css, strpos($css, "this site's cards"))) : 0, 'url' => $H . '/free/site/' . $id . '/', 'err' => $rec['err'] ?? ''];
foreach (array_slice(array_values(array_unique($am[1])), 0, 2) as $aid) { $r = one('SELECT svg, w, h FROM art WHERE id = ?', [$aid]); if ($r) $o['svgs'][] = ['w' => (int)$r['w'], 'h' => (int)$r['h'], 'len' => strlen((string)$r['svg']), 'b64' => base64_encode((string)$r['svg'])]; }
[$s] = (function ($u) { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_HEADER => true]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s . ' ' . (preg_match('/content-type: ([^\r\n]+)/i', $b, $m) ? $m[1] : '') . ' ' . (preg_match('/content-security-policy: ([^\r\n]+)/i', $b, $m) ? $m[1] : '')]; })($H . '/dar-sync.php?art=' . ($am[1][0] ?? 'x'));
$o['serve'] = $s;
$left = 0; foreach (q("SELECT v FROM meta WHERE k LIKE 'gx:%'")->fetchAll() as $row) $left += preg_match_all('/\{\{\s*img\b/', (string)$row['v']); $o['gallery_left'] = $left; $o['mig'] = meta_get('art_mig1') !== null;
echo json_encode($o, JSON_UNESCAPED_SLASHES), "\n";
