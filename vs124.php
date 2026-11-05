<?php
// After 3.33.77: two cards asked as a visitor asks them (a post with one photograph's place, a status with none), each a job — the
// designer's draft, then the art director's pass — followed until ready; the trial site's photographs since
ini_set('display_errors', '0'); @set_time_limit(700);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION];
$post = function (array $body, int $t = 30): array { $c = curl_init('https://dar.ink/dar-sync.php'); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $t, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE), CURLOPT_HTTPHEADER => ['Content-Type: application/json']]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s, json_decode($b, true) ?: ['raw' => mb_substr($b, 0, 300)]]; };
$asks = [['text' => 'فيلا للبيع في حي الياسمين شمال الرياض، مساحة الأرض 450 متر، 5 غرف نوم و6 دورات مياه، مجلس رجال ومجلس نساء، مسبح، السعر 2.5 مليون ريال قابل للتفاوض. للتواصل 0551234567 — مكتب الياسمين العقاري', 'shape' => 'post', 'photos' => 1],
         ['text' => 'شقة للإيجار في حي النرجس، 3 غرف وصالة ومطبخ راكب، الدور الثاني، مصعد، 55 ألف سنوي. واتساب 0509876543', 'shape' => 'story', 'photos' => 0]];
$jobs = []; $t0 = microtime(true);
foreach ($asks as $i => $a) { [$s, $j] = $post(['op' => 'free.card', 'payload' => $a, 'app' => 'site']); $jobs[$i] = (string)($j['data']['id'] ?? ''); $o['cards'][$i] = ['http' => $s, 'id' => $jobs[$i], 'err' => $j['error'] ?? null, 'stages' => []]; }
for ($k = 0; $k < 120; $k++) {
  sleep(3); $open = 0;
  foreach ($jobs as $i => $id) { if ($id === '' || isset($o['cards'][$i]['svg'])) continue; $open++;
    [$s, $j] = $post(['op' => 'free.card_status', 'payload' => ['id' => $id], 'app' => 'site']); $d = $j['data'] ?? []; $st = (string)($d['status'] ?? 'x') . '/' . (string)($d['stage'] ?? '');
    if (!$o['cards'][$i]['stages'] || explode('@', end($o['cards'][$i]['stages']))[0] !== $st) $o['cards'][$i]['stages'][] = $st . '@' . round(microtime(true) - $t0) . 's';
    if (($d['status'] ?? '') === 'ready') { $o['cards'][$i]['svg'] = (string)$d['svg']; $o['cards'][$i]['by'] = $d['by'] ?? ''; $o['cards'][$i]['len'] = strlen((string)$d['svg']); }
    if (($d['status'] ?? '') === 'failed' || !empty($j['error'])) $o['cards'][$i]['svg'] = ''; }
  if (!$open) break;
}
$all = (string)meta_get('gs:mw3rqekr42c945'); preg_match_all('/dar-sync\.php\?f=([0-9a-f]{48})/', $all, $fm);
$o['site'] = ['photos' => count(array_unique($fm[1])), 'left' => preg_match_all('/\{\{\s*img\b/', $all), 'jobs' => (int)(one("SELECT COUNT(*) n FROM meta WHERE k LIKE 'phj:%'")['n'] ?? 0)];
$ex = 0; $exl = 0; foreach (q("SELECT v FROM meta WHERE k LIKE 'gx:%'")->fetchAll() as $row) { preg_match_all('/dar-sync\.php\?f=[0-9a-f]{48}/', (string)$row['v'], $mm); $ex += count(array_unique($mm[0])); $exl += preg_match_all('/\{\{\s*img\b/', (string)$row['v']); } $o['gallery'] = ['photos' => $ex, 'left' => $exl];
$o['pad'] = str_repeat('x', 80000);
echo json_encode($o, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
