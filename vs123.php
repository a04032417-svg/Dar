<?php
// After 3.33.76: the card designer asked as a visitor asks it (two cards: a post with one photograph's place, a status with none); the
// trial site's photographs since (the jobs now kept across minutes); a hub page's call to action
ini_set('display_errors', '0'); @set_time_limit(600);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION, 'core' => preg_match("/VERSION = '([^']+)'/", (string)@file_get_contents($R . '/assets/core.js', false, null, 0, 4000), $vm) ? $vm[1] : ''];
$post = function (array $body, int $t = 75): array { $c = curl_init('https://dar.ink/dar-sync.php'); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $t, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE), CURLOPT_HTTPHEADER => ['Content-Type: application/json']]); $t0 = microtime(true); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s, json_decode($b, true) ?: ['raw' => mb_substr($b, 0, 300)], round(microtime(true) - $t0, 1)]; };
$asks = [['text' => 'فيلا للبيع في حي الياسمين شمال الرياض، مساحة الأرض 450 متر، 5 غرف نوم و6 دورات مياه، مجلس رجال ومجلس نساء، مسبح، السعر 2.5 مليون ريال قابل للتفاوض. للتواصل 0551234567 — مكتب الياسمين العقاري', 'shape' => 'post', 'photos' => 1],
         ['text' => 'شقة للإيجار في حي النرجس، 3 غرف وصالة ومطبخ راكب، الدور الثاني، مصعد، 55 ألف سنوي. واتساب 0509876543', 'shape' => 'story', 'photos' => 0]];
foreach ($asks as $i => $a) { [$s, $j, $t] = $post(['op' => 'free.card', 'payload' => $a, 'app' => 'site']); $d = $j['data'] ?? []; $o['cards'][$i] = ['http' => $s, 'secs' => $t, 'ok' => !empty($d['svg']), 'by' => $d['by'] ?? '', 'len' => strlen((string)($d['svg'] ?? '')), 'err' => $j['error'] ?? null, 'svg' => (string)($d['svg'] ?? '')]; }
$all = (string)meta_get('gs:mw3rqekr42c945'); preg_match_all('/dar-sync\.php\?f=([0-9a-f]{48})/', $all, $fm);
$o['site'] = ['photos' => count(array_unique($fm[1])), 'left' => preg_match_all('/\{\{\s*img\b/', $all), 'jobs' => (int)(one("SELECT COUNT(*) n FROM meta WHERE k LIKE 'phj:%'")['n'] ?? 0)];
try { $pg = one("SELECT lang, slug FROM grow_pages WHERE lang = 'ar' AND status = 'live' AND aud = 'office' AND kind != 'tool' AND kind != 'service' ORDER BY upd DESC LIMIT 1"); if ($pg) { $u = grow_url('ar', (string)$pg['slug']); $h = (string)@file_get_contents($u); $o['hub'] = ['url' => $u, 'card' => substr_count($h, '/free/card/')]; } } catch (Throwable $e) { $o['hub'] = 'x:' . $e->getMessage(); }
$fc = (string)@file_get_contents('https://dar.ink/free/card/'); $o['page'] = ['len' => strlen($fc), 'tool' => str_contains($fc, 'data-tool="card"')];
$o['font'] = strlen((string)@file_get_contents('https://dar.ink/assets/fonts/plex-ar-700.woff2'));
$o['pad'] = str_repeat('x', 80000);
echo json_encode($o, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
