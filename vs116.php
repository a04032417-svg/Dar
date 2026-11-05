<?php
// After 3.33.72 (every model's own free allowance on the same keys): the studio's kind of call answered now (who answered), two
// pictures drawn by the AI (kept, to look at), then a visitor's new website made live with its pictures drawn
ini_set('display_errors', '0'); @set_time_limit(1300);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION, 'ready' => ai_ready()]; $c = grow_owner(); $H = 'https://dar.ink';
$t = microtime(true); try { $r = ai_complete($c, ['messages' => [['role' => 'system', 'content' => 'Answer with JSON only: {"ok":true,"word":"<one Arabic word for home>"}'], ['role' => 'user', 'content' => 'now']], 'json' => true, 'tier' => 'standard', 'lead' => STUDIO_LEAD, 'avoid' => STUDIO_AVOID, 'max_tokens' => 200, 'deadline' => 30, 'cache' => false]); $o['json_call'] = [($r['provider'] ?? '') . '/' . ($r['model'] ?? ''), round(microtime(true) - $t, 1), $r['data'] ?? null]; } catch (Throwable $e) { $o['json_call'] = 'err ' . $e->getMessage(); }
$look = ['brand' => '#1f3a2e', 'accent' => '#c8a24a', 'bg' => '#f7f3ec', 'ink' => '#1a1a1a', 'mode' => 'light', 'arch' => 'contemporary Najdi villas, Riyadh'];
foreach ([['wide', 'contemporary Najdi villa facade with palm trees at golden hour, north Riyadh'], ['square', 'bright modern apartment living room with floor-to-ceiling windows, Riyadh']] as [$sz, $p]) {
    $t = microtime(true); $svg = art_draw($c, $p, $sz, $look, 110); $last = one('SELECT provider, model FROM ai_log WHERE ok = 1 ORDER BY id DESC LIMIT 1');
    $o['draw'][] = ['sz' => $sz, 's' => round(microtime(true) - $t, 1), 'len' => strlen($svg), 'shapes' => preg_match_all('/<(path|rect|circle|ellipse|polygon|polyline|line)\b/i', $svg), 'by' => ($last['provider'] ?? '') . '/' . ($last['model'] ?? '')];
    if ($svg !== '') $o['svg'][$sz] = base64_encode($svg);
}
$post = function (string $u, $body, int $t = 70): array { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $t, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE), CURLOPT_HTTPHEADER => ['Content-Type: application/json']]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s, $b]; };
$t1 = microtime(true);
[$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.site', 'payload' => ['name' => 'مكتب الروابي العقاري', 'city' => 'الرياض', 'kind' => 'office', 'about' => 'بيع وتأجير الفلل والشقق والأراضي في شرق الرياض، وإدارة الأملاك للملاك والمستثمرين بخدمة شخصية'], 'app' => 'site']);
$id = (string)(json_decode($b, true)['data']['id'] ?? ''); $st = ''; $stages = [];
if ($id !== '') { for ($i = 0; $i < 260; $i++) { sleep(3); [$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.site_status', 'payload' => ['id' => $id], 'app' => 'site']); $jj = json_decode($b, true)['data'] ?? []; $st = (string)($jj['status'] ?? 'x'); $sg = (string)($jj['stage'] ?? ''); if ($sg !== '' && (!$stages || explode('@', end($stages))[0] !== $sg)) $stages[] = $sg . '@' . round(microtime(true) - $t1) . 's'; if (in_array($st, ['ready', 'failed'], true)) break; } }
$rec = o2a(jdec((string)meta_get('gs:' . $id))); $all = json_encode($rec['d'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); preg_match_all('/dar-sync\.php\?art=([0-9a-f]{32})/', $all, $am);
$o['site'] = ['status' => $st, 'stages' => $stages, 'secs' => round(microtime(true) - $t1), 'by' => $rec['by'] ?? '', 'drawn' => count(array_unique($am[1])), 'left' => preg_match_all('/\{\{\s*img\b/', $all), 'url' => $H . '/free/site/' . $id . '/'];
$left = 0; foreach (q("SELECT v FROM meta WHERE k LIKE 'gx:%'")->fetchAll() as $row) $left += preg_match_all('/\{\{\s*img\b/', (string)$row['v']); $o['gallery_left'] = $left;
$o['ai_ok_15m'] = q('SELECT provider, model, COUNT(*) n FROM ai_log WHERE ok = 1 AND at > ? GROUP BY provider, model ORDER BY n DESC LIMIT 12', [now_ms() - 900000])->fetchAll();
echo json_encode($o, JSON_UNESCAPED_SLASHES), "\n";
