<?php
// After 3.33.59: the clients' requests board live (page, board op, MCP tool), and the MCP Registry entry brought to this version
ini_set('display_errors', '1'); error_reporting(E_ALL);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION]; $H = 'https://dar.ink';
$post = function (string $u, $body, array $h = []): array { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 40, CURLOPT_POST => true, CURLOPT_POSTFIELDS => is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json', 'Accept: application/json, text/event-stream'], $h)]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s, $b]; };
$c = curl_init($H . '/free/requests/'); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]); $b = (string)curl_exec($c); $o['page'] = [curl_getinfo($c, CURLINFO_HTTP_CODE), preg_match('~<title>([^<]*)</title>~u', $b, $m) ? $m[1] : '']; curl_close($c);
[$s, $b] = $post($H . '/dar-sync.php', ['op' => 'demand.board', 'payload' => new stdClass(), 'app' => 'site']); $j = json_decode($b, true); $o['board'] = [$s, !empty($j['ok']), count($j['data']['requests'] ?? [])];
[$s, $b] = $post($H . '/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']); $j = json_decode($b, true); $o['mcp_tools'] = array_column($j['result']['tools'] ?? [], 'name');
$c = curl_init($H . '/sitemap.xml'); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]); $o['sitemap_requests'] = substr_count((string)curl_exec($c), '/free/requests/'); curl_close($c);
// the registry: same domain key, this version
$c = curl_init($H . '/.well-known/mcp/server.json'); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]); $card = json_decode((string)curl_exec($c), true); curl_close($c);
$card['description'] = 'Real-estate tools: commission, lease, FAL licence, AI listings, listing pages, property requests.';
$skb = base64_decode((string)(meta_get('mcp_reg_sk') ?? '')); $ts = gmdate('Y-m-d\TH:i:s\Z');
[$s, $b] = $post('https://registry.modelcontextprotocol.io/v0/auth/http', ['domain' => 'dar.ink', 'timestamp' => $ts, 'signed_timestamp' => bin2hex(sodium_crypto_sign_detached($ts, $skb))]); $tok = (string)(json_decode($b, true)['registry_token'] ?? '');
if ($tok !== '') { [$s, $b] = $post('https://registry.modelcontextprotocol.io/v0/publish', $card, ['Authorization: Bearer ' . $tok]); $j = json_decode($b, true); $o['registry'] = [$s, $j['server']['version'] ?? substr($b, 0, 300)]; } else $o['registry'] = ['auth', substr($b, 0, 200)];
$c = curl_init($H . '/.well-known/mcp-registry-auth'); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]); curl_exec($c); $o['auth_file'] = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c);
$c = curl_init('https://api.indexnow.org/indexnow'); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_POST => true, CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'], CURLOPT_POSTFIELDS => json_encode(['host' => 'dar.ink', 'key' => 'f0862deb61759210d196dd529c2da31e', 'keyLocation' => $H . '/f0862deb61759210d196dd529c2da31e.txt', 'urlList' => [$H . '/free/requests/', $H . '/free/', $H . '/', $H . '/llms.txt']])]); curl_exec($c); $o['indexnow'] = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c);
$o['mcp_use'] = []; foreach (q("SELECT k, v FROM meta WHERE k LIKE 'mcp:%' ORDER BY k DESC LIMIT 3")->fetchAll() as $r) $o['mcp_use'][$r['k']] = jdec((string)$r['v']);
$o['health'] = jdec((string)(meta_get('health') ?? ''))->reasons ?? null;
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
