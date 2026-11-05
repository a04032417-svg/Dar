<?php
// After 3.33.62: a website designed whole by the live AI in the background (started at once, then asked until ready; its design returned
// for a look), the registry entry at this version
ini_set('display_errors', '1'); error_reporting(E_ALL); @set_time_limit(500);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION]; $H = 'https://dar.ink';
$get = function (string $u): array { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_HEADER => true]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); $hs = curl_getinfo($c, CURLINFO_HEADER_SIZE); curl_close($c); return [$s, substr($b, $hs), substr($b, 0, $hs)]; };
$post = function (string $u, $body, array $h = [], int $t = 40): array { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $t, CURLOPT_POST => true, CURLOPT_POSTFIELDS => is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json', 'Accept: application/json, text/event-stream'], $h)]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s, $b]; };
[$s, $b] = $post($H . '/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']); $o['mcp_tools'] = count(json_decode($b, true)['result']['tools'] ?? []);
// 2. a website designed whole by the live AI, as a visitor makes it
$t0 = microtime(true);
[$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.site', 'payload' => ['name' => 'مكتب الياسمين العقاري', 'city' => 'الرياض', 'kind' => 'office', 'about' => 'بيع وتأجير الفلل والشقق الفاخرة وإدارة الأملاك في أحياء شمال الرياض، بخدمة شخصية ومتابعة حتى استلام المفتاح', 'phone' => '+966 50 000 0000', 'lang' => 'ar'], 'app' => 'site'], [], 50);
$j = json_decode($b, true); $o['start'] = [$s, !empty($j['ok']), $j['data']['status'] ?? ($j['error']['code'] ?? substr($b, 0, 300)), round(microtime(true) - $t0, 1) . 's'];
$id = (string)($j['data']['id'] ?? ''); $st = '';
if ($id !== '') { [$s, $b] = $get($H . '/free/site/' . $id . '/'); $o['page_while'] = $s; for ($i = 0; $i < 70; $i++) { sleep(4); [$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.site_status', 'payload' => ['id' => $id], 'app' => 'site']); $st = (string)(json_decode($b, true)['data']['status'] ?? 'x'); if ($st !== 'working') break; } $o['done'] = [$st, round(microtime(true) - $t0, 1) . 's', json_decode($b, true)['data']['err'] ?? ''];
  if ($st !== 'ready') { $rr = o2a(jdec((string)meta_get('gs:' . $id))); $o['fail_rec'] = ['st' => $rr['st'] ?? '', 'err' => $rr['err'] ?? '']; $o['faults'] = array_slice(array_map(fn($x) => substr($x, 0, 220), @file(data_dir() . '/server-errors.log', FILE_IGNORE_NEW_LINES) ?: []), -4); } }
if ($id !== '' && $st === 'ready') {
    $rec = o2a(jdec((string)meta_get('gs:' . $id))); $o['by'] = $rec['by'] ?? '';
    $all = json_encode($rec['d'], JSON_UNESCAPED_UNICODE); $o['photos'] = substr_count($all, 'data-ai-img'); $o['img_left'] = preg_match_all('/\{\{\s*img\b/', $all);
    $o['css_len'] = strlen((string)($rec['d']['settings']['css'] ?? '')); $o['pages'] = array_map(fn($p) => ($p['path'] ?: '/') . ' ' . count($p['sections']), $rec['d']['pages'] ?? []);
    [$s, $b, $hd] = $get($H . '/free/site/' . $id . '/'); $o['draft'] = [$s, strlen($b), (bool)preg_match('/<script(?![^>]*src=)/i', preg_replace('#<script type="application/ld\+json">.*?</script>#s', '', $b)), substr_count($b, 'class="fr"')];
    $o['design_gz'] = base64_encode(gzencode($all, 9)); $o['meta'] = ['n' => $rec['n'], 'c' => $rec['c'], 'l' => $rec['l'], 'p' => $rec['p']];
    q('DELETE FROM meta WHERE k = ?', ['gs:' . $id]);
}
// 3. the registry: same domain key, this version
[$s, $b] = $get($H . '/.well-known/mcp/server.json'); $card = json_decode($b, true);
$skb = base64_decode((string)(meta_get('mcp_reg_sk') ?? '')); $ts = gmdate('Y-m-d\TH:i:s\Z');
if ($skb !== '' && function_exists('sodium_crypto_sign_detached') && is_array($card)) {
    [$s, $b] = $post('https://registry.modelcontextprotocol.io/v0/auth/http', ['domain' => 'dar.ink', 'timestamp' => $ts, 'signed_timestamp' => bin2hex(sodium_crypto_sign_detached($ts, $skb))]); $tok = (string)(json_decode($b, true)['registry_token'] ?? '');
    if ($tok !== '') { [$s, $b] = $post('https://registry.modelcontextprotocol.io/v0/publish', $card, ['Authorization: Bearer ' . $tok]); $o['registry'] = [$s, json_decode($b, true)['server']['version'] ?? substr($b, 0, 200)]; } else $o['registry'] = ['auth', substr($b, 0, 160)];
}
$o['health'] = jdec((string)(meta_get('health') ?? ''))->reasons ?? null;
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
