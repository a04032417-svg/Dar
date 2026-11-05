<?php
// After 3.33.65: the studio's new pages made live (its team's stages, its words, three photographs), the AI tools answering live, the
// gallery's examples made again on the new pages, and the funnel as it stands (who comes, who tries, who pays)
ini_set('display_errors', '1'); error_reporting(E_ALL); @set_time_limit(1500);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION]; $H = 'https://dar.ink';
$get = function (string $u): array { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_FOLLOWLOCATION => false]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s, $b]; };
$post = function (string $u, $body, int $t = 70): array { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $t, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE), CURLOPT_HTTPHEADER => ['Content-Type: application/json']]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s, $b]; };
$thumb = function (string $u) use ($get): string { [$s, $b] = $get($u); if ($s !== 200 || !function_exists('imagecreatefromstring')) return ''; $im = @imagecreatefromstring($b); if (!$im) return ''; $w = imagesx($im); $h = imagesy($im); $nw = 520; $nh = (int)round($h * $nw / max(1, $w)); $t = imagecreatetruecolor($nw, $nh); imagecopyresampled($t, $im, 0, 0, 0, 0, $nw, $nh, $w, $h); ob_start(); imagejpeg($t, null, 60); return base64_encode((string)ob_get_clean()); };
// 1. the funnel (the last 7 days)
$d7 = gmdate('Y-m-d', time() - 7 * 86400);
$o['grow_7d'] = one('SELECT SUM(views) AS views, SUM(bots) AS bots, SUM(se) AS se, SUM(clicks) AS clicks FROM grow_daily WHERE day >= ?', [$d7]);
$o['tenants_14d'] = q("SELECT status, COUNT(*) AS n FROM tenants WHERE created > ? GROUP BY status", [now_ms() - 14 * 86400000])->fetchAll();
$o['drafts'] = ['live' => (int)(one("SELECT COUNT(*) AS n FROM meta WHERE k LIKE 'gs:%'")['n'] ?? 0)];
$o['pay_14d'] = q("SELECT type, status, COUNT(*) AS n FROM pay_events WHERE at > ? GROUP BY type, status", [now_ms() - 14 * 86400000])->fetchAll();
// 2. the AI tools, live
foreach ([['listing', 'شقة 3 غرف في حي النرجس بالرياض، 180 متر، دور ثاني، مطبخ راكب، قريبة من المدارس، للبيع 950 ألف'], ['commission', 'بعت فيلا بمليون ونص والعمولة اثنين ونص بالمية مع ضريبة 15 بالمية ونصها للمكتب الثاني'], ['contract', 'أبي عقد تسويق حصري بيني (مكتب الياسمين) وبين المالك على فيلا في الملقا لمدة 90 يوم والعمولة 2.5%']] as [$k, $t]) {
    $t0 = microtime(true); [$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.ask', 'payload' => ['task' => $k, 'text' => $t], 'app' => 'site'], 75); $j = json_decode($b, true);
    $o['ask'][$k] = [$s, $j['ok'] ?? null, $j['error']['code'] ?? '', round(microtime(true) - $t0, 1), $j['data']['by'] ?? '', $k === 'commission' ? ['c' => $j['data']['commission'] ?? null, 't' => $j['data']['total'] ?? null, 'sh' => $j['data']['shares'] ?? null] : mb_substr((string)($j['data']['text'] ?? ''), 0, 700)];
}
[$s] = $get($H . '/free/fal/'); $o['fal'] = $s;
// 3. a visitor's website on the studio's new pages
$t0 = microtime(true);
[$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.site', 'payload' => ['name' => 'مكتب الياسمين العقاري', 'city' => 'الرياض', 'kind' => 'office', 'about' => 'بيع وتأجير الفلل والشقق وإدارة الأملاك في أحياء شمال الرياض: الملقا والياسمين وحطين والنرجس، بخدمة شخصية ومتابعة حتى استلام المفتاح', 'phone' => '+966 50 000 0000', 'lang' => 'ar'], 'app' => 'site'], 50);
$j = json_decode($b, true); $id = (string)($j['data']['id'] ?? ''); $st = ''; $stages = [];
if ($id !== '') { for ($i = 0; $i < 110; $i++) { sleep(3); [$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.site_status', 'payload' => ['id' => $id], 'app' => 'site']); $jj = json_decode($b, true)['data'] ?? []; $st = (string)($jj['status'] ?? 'x'); $sg = (string)($jj['stage'] ?? ''); if ($sg !== '' && (!$stages || explode('@', end($stages))[0] !== $sg)) $stages[] = $sg . '@' . round(microtime(true) - $t0) . 's'; if ($st !== 'working') break; } }
$o['site'] = ['status' => $st, 'stages' => $stages, 'secs' => round(microtime(true) - $t0)];
if ($st === 'ready') {
    $rec = o2a(jdec((string)meta_get('gs:' . $id))); $all = json_encode($rec['d'], JSON_UNESCAPED_UNICODE); $o['site'] += ['url' => $H . '/free/site/' . $id . '/', 'by' => $rec['by'] ?? '', 'team' => $rec['team'] ?? null, 'photos' => substr_count($all, 'data-ai-img'), 'pages' => array_map(fn($p) => ($p['path'] ?: '/') . ' ' . count($p['sections']), $rec['d']['pages'] ?? [])];
    $o['copy'] = array_map(fn($sc) => mb_substr(trim((string)preg_replace('/\s+/u', ' ', strip_tags(str_replace('<', ' <', (string)preg_replace('/\{\{[^}]*\}\}/', '', (string)$sc['html']))))), 0, 600), $rec['d']['pages'][0]['sections'] ?? []);
    preg_match_all('/dar-sync\.php\?f=[0-9a-f]{48}/', $all, $pm); $o['photo_urls'] = array_values(array_unique($pm[0])); foreach (array_slice($o['photo_urls'], 0, 6) as $pu) $o['thumbs'][] = $thumb($H . '/' . $pu);
    $o['design_gz'] = base64_encode(gzencode($all, 9));
}
// 4. the gallery: its first four examples made again on the new pages
$o['examples'] = isite_ex_make(4);
$o['health'] = jdec((string)(meta_get('health') ?? ''))->reasons ?? null;
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
