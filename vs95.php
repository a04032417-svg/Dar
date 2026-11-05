<?php
// After 3.33.64 (استوديو دار: the team designs the website on the studio's design system): a visitor's website made live (the team's stages
// seen as they go), its copy and three of its photographs returned for a look; the first examples of the gallery made; the registry entry
ini_set('display_errors', '1'); error_reporting(E_ALL); @set_time_limit(1500);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION]; $H = 'https://dar.ink';
$get = function (string $u): array { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_HEADER => true]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); $hs = curl_getinfo($c, CURLINFO_HEADER_SIZE); curl_close($c); return [$s, substr($b, $hs), substr($b, 0, $hs)]; };
$post = function (string $u, $body, array $h = [], int $t = 40): array { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $t, CURLOPT_POST => true, CURLOPT_POSTFIELDS => is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json', 'Accept: application/json, text/event-stream'], $h)]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s, $b]; };

$thumb = function (string $u) use ($get): string { [$s, $b] = $get($u); if ($s !== 200 || !function_exists('imagecreatefromstring')) return ''; $im = @imagecreatefromstring($b); if (!$im) return ''; $w = imagesx($im); $h = imagesy($im); $nw = 420; $nh = (int)round($h * $nw / max(1, $w)); $t = imagecreatetruecolor($nw, $nh); imagecopyresampled($t, $im, 0, 0, 0, 0, $nw, $nh, $w, $h); ob_start(); imagejpeg($t, null, 62); return base64_encode((string)ob_get_clean()); };
// 1. a visitor's website, made by the team (the stages seen as they go)
$t0 = microtime(true);
[$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.site', 'payload' => ['name' => 'مكتب الياسمين العقاري', 'city' => 'الرياض', 'kind' => 'office', 'about' => 'بيع وتأجير الفلل والشقق الفاخرة وإدارة الأملاك في أحياء شمال الرياض: الملقا والياسمين وحطين والنرجس، بخدمة شخصية ومتابعة حتى استلام المفتاح', 'phone' => '+966 50 000 0000', 'lang' => 'ar'], 'app' => 'site'], [], 50);
$j = json_decode($b, true); $o['start'] = [$s, !empty($j['ok']), $j['data']['status'] ?? ($j['error']['code'] ?? substr($b, 0, 300)), round(microtime(true) - $t0, 1) . 's'];
$id = (string)($j['data']['id'] ?? ''); $st = ''; $stages = [];
if ($id !== '') { [$s, $b] = $get($H . '/free/site/' . $id . '/'); $o['page_while'] = [$s, (bool)preg_match('/<ol class="team">/', $b)];
  for ($i = 0; $i < 110; $i++) { sleep(3); [$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.site_status', 'payload' => ['id' => $id], 'app' => 'site']); $jj = json_decode($b, true)['data'] ?? []; $st = (string)($jj['status'] ?? 'x'); $sg = (string)($jj['stage'] ?? ''); if ($sg !== '' && end($stages) !== $sg . '@' . 0) { $last = $stages ? explode('@', end($stages))[0] : ''; if ($last !== $sg) $stages[] = $sg . '@' . round(microtime(true) - $t0) . 's'; } if ($st !== 'working') break; }
  $o['stages'] = $stages; $o['done'] = [$st, round(microtime(true) - $t0, 1) . 's', json_decode($b, true)['data']['err'] ?? ''];
  if ($st !== 'ready') { $rr = o2a(jdec((string)meta_get('gs:' . $id))); $o['fail_rec'] = ['st' => $rr['st'] ?? '', 'err' => $rr['err'] ?? '']; $o['faults'] = array_slice(array_map(fn($x) => substr($x, 0, 260), @file(data_dir() . '/server-errors.log', FILE_IGNORE_NEW_LINES) ?: []), -6); } }
if ($id !== '' && $st === 'ready') {
    $rec = o2a(jdec((string)meta_get('gs:' . $id))); $o['by'] = $rec['by'] ?? ''; $o['team'] = $rec['team'] ?? null; $o['url'] = $H . '/free/site/' . $id . '/';
    $all = json_encode($rec['d'], JSON_UNESCAPED_UNICODE); $o['photos'] = substr_count($all, 'data-ai-img'); $o['img_left'] = preg_match_all('/\{\{\s*img\b/', $all);
    $o['photo_urls'] = array_values(array_slice(array_unique(preg_match_all('/dar-sync\.php\?f=[0-9a-f]{48}/', $all, $pm) ? $pm[0] : []), 0, 8));
    foreach (array_slice($o['photo_urls'], 0, 3) as $pu) $o['thumbs'][] = $thumb($H . '/' . $pu);
    $o['pages'] = array_map(fn($p) => ($p['path'] ?: '/') . ' ' . count($p['sections']), $rec['d']['pages'] ?? []);
    $o['copy'] = array_map(fn($sc) => trim((string)preg_replace('/\s+/u', ' ', strip_tags(str_replace('<', ' <', (string)preg_replace('/\{\{[^}]*\}\}/', '', (string)$sc['html']))))), $rec['d']['pages'][0]['sections'] ?? []);
    [$s, $b, $hd] = $get($H . '/free/site/' . $id . '/'); $o['draft'] = [$s, strlen($b), (bool)preg_match('/<script(?![^>]*src=)/i', preg_replace('#<script type="application/ld\+json">.*?</script>#s', '', $b)), substr_count($b, 'class="fr"'), (bool)strpos($b, '--s-accent')];
    $o['design_gz'] = base64_encode(gzencode($all, 9));
}
// 2. the gallery's first examples, each made by the team
$o['examples'] = isite_ex_make(3);
[$s, $b] = $get($H . '/free/website/examples/'); $o['gallery'] = [$s, substr_count($b, '/free/website/examples/') ];
foreach (ISITE_EX as $x) if (($r = isite_ex_rec($x[0]))) { [$s] = $get($H . '/free/website/examples/' . $x[0] . '/'); $o['ex_pages'][$x[0]] = [$s, $r['by'] ?? '', $r['team']['direction'] ?? '', substr_count(json_encode($r['d']), 'data-ai-img')]; }
// 3. the registry: same domain key, this version
[$s, $b] = $get($H . '/.well-known/mcp/server.json'); $card = json_decode($b, true);
$skb = base64_decode((string)(meta_get('mcp_reg_sk') ?? '')); $ts = gmdate('Y-m-d\TH:i:s\Z');
if ($skb !== '' && function_exists('sodium_crypto_sign_detached') && is_array($card)) {
    [$s, $b] = $post('https://registry.modelcontextprotocol.io/v0/auth/http', ['domain' => 'dar.ink', 'timestamp' => $ts, 'signed_timestamp' => bin2hex(sodium_crypto_sign_detached($ts, $skb))]); $tok = (string)(json_decode($b, true)['registry_token'] ?? '');
    if ($tok !== '') { [$s, $b] = $post('https://registry.modelcontextprotocol.io/v0/publish', $card, ['Authorization: Bearer ' . $tok]); $o['registry'] = [$s, json_decode($b, true)['server']['version'] ?? substr($b, 0, 200)]; } else $o['registry'] = ['auth', substr($b, 0, 160)];
}
$o['health'] = jdec((string)(meta_get('health') ?? ''))->reasons ?? null;
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
