<?php
// After 3.33.60: the instant website live end to end (a draft made by the live AI, drawn, then removed), the MCP tool, the sitemap,
// the MCP Registry entry at this version, IndexNow
ini_set('display_errors', '1'); error_reporting(E_ALL); @set_time_limit(400);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION]; $H = 'https://dar.ink';
$get = function (string $u): array { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_HEADER => true]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); $hs = curl_getinfo($c, CURLINFO_HEADER_SIZE); curl_close($c); return [$s, substr($b, $hs), substr($b, 0, $hs)]; };
$post = function (string $u, $body, array $h = [], int $t = 40): array { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $t, CURLOPT_POST => true, CURLOPT_POSTFIELDS => is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json', 'Accept: application/json, text/event-stream'], $h)]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s, $b]; };
[$s, $b] = $get($H . '/free/website/'); $o['page'] = [$s, preg_match('~<title>([^<]*)</title>~u', $b, $m) ? $m[1] : ''];
[$s, $b] = $get($H . '/free/grade/'); $o['page_grade'] = [$s, preg_match('~<title>([^<]*)</title>~u', $b, $m) ? $m[1] : ''];
[$s, $b] = $get($H . '/sitemap.xml'); $o['sitemap_website'] = substr_count($b, '/free/website/'); $o['sitemap_grade'] = substr_count($b, '/free/grade/');
[$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.grade', 'payload' => ['url' => 'example.com'], 'app' => 'site'], [], 40); $j = json_decode($b, true); $o['grade_example'] = [$s, $j['data']['score'] ?? ($j['error']['code'] ?? substr($b, 0, 150)), $j['data']['name'] ?? ''];
[$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.grade', 'payload' => ['url' => 'http://127.0.0.1/'], 'app' => 'site'], [], 20); $j = json_decode($b, true); $o['grade_private'] = $j['error']['code'] ?? 'ALLOWED?';
[$s, $b] = $get($H . '/llms.txt'); $o['llms_website'] = substr_count($b, '/free/website/');
// a draft by the live AI (the free providers), as a visitor makes it
$t0 = microtime(true);
[$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.site', 'payload' => ['name' => 'مكتب تجربة دار العقاري', 'city' => 'الرياض', 'kind' => 'office', 'about' => 'بيع وتأجير الشقق والفلل وإدارة الأملاك في شمال الرياض', 'lang' => 'ar'], 'app' => 'site'], [], 150);
$j = json_decode($b, true); $o['make'] = [$s, !empty($j['ok']), $j['data']['pages'] ?? ($j['error']['code'] ?? substr($b, 0, 200)), round(microtime(true) - $t0, 1) . 's'];
$id = (string)($j['data']['id'] ?? '');
if ($id !== '') {
    $rec = o2a(jdec((string)meta_get('gs:' . $id))); $o['by'] = $rec['by'] ?? '';
    [$s, $b, $hd] = $get($H . '/free/site/' . $id . '/'); $o['draft'] = [$s, (bool)preg_match('/x-robots-tag:\s*noindex/i', $hd), str_contains($b, 'id="dar-isite"'), str_contains($b, '/app/#/register?site=' . $id), mb_strlen(strip_tags($b)), preg_match_all('~<section~', $b)];
    $p2 = (string)($rec['d']['pages'][1]['path'] ?? '');
    if ($p2 !== '') { [$s, $b] = $get($H . '/free/site/' . $id . '/' . $p2); $o['draft_page2'] = [$p2, $s]; }
    $o['pages'] = array_map(fn($p) => ($p['title'] ?? '') . ' (' . count($p['sections'] ?? []) . ')', $rec['d']['pages'] ?? []);
    q('DELETE FROM meta WHERE k = ?', ['gs:' . $id]); $o['removed'] = meta_get('gs:' . $id) === null;
}
[$s, $b] = $post($H . '/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']); $j = json_decode($b, true); $o['mcp_tools'] = array_column($j['result']['tools'] ?? [], 'name');
// the registry: same domain key, this version
[$s, $b] = $get($H . '/.well-known/mcp/server.json'); $card = json_decode($b, true); $o['card'] = [$card['version'] ?? '', $card['description'] ?? ''];
$skb = base64_decode((string)(meta_get('mcp_reg_sk') ?? '')); $ts = gmdate('Y-m-d\TH:i:s\Z');
if ($skb !== '' && function_exists('sodium_crypto_sign_detached')) {
    [$s, $b] = $post('https://registry.modelcontextprotocol.io/v0/auth/http', ['domain' => 'dar.ink', 'timestamp' => $ts, 'signed_timestamp' => bin2hex(sodium_crypto_sign_detached($ts, $skb))]); $tok = (string)(json_decode($b, true)['registry_token'] ?? '');
    if ($tok !== '') { [$s, $b] = $post('https://registry.modelcontextprotocol.io/v0/publish', $card, ['Authorization: Bearer ' . $tok]); $j = json_decode($b, true); $o['registry'] = [$s, $j['server']['version'] ?? substr($b, 0, 300)]; } else $o['registry'] = ['auth', substr($b, 0, 200)];
} else $o['registry'] = 'no key or no sodium';
$c = curl_init('https://api.indexnow.org/indexnow'); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_POST => true, CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'], CURLOPT_POSTFIELDS => json_encode(['host' => 'dar.ink', 'key' => 'f0862deb61759210d196dd529c2da31e', 'keyLocation' => $H . '/f0862deb61759210d196dd529c2da31e.txt', 'urlList' => [$H . '/free/grade/', $H . '/free/grade/?lang=en', $H . '/free/website/', $H . '/free/website/?lang=en', $H . '/free/website/?lang=fr', $H . '/free/', $H . '/', $H . '/app/', $H . '/llms.txt']])]); curl_exec($c); $o['indexnow'] = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c);
$o['health'] = jdec((string)(meta_get('health') ?? ''))->reasons ?? null;
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
