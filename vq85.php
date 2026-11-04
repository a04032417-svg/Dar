<?php
ini_set('display_errors', '1'); error_reporting(E_ALL); register_shutdown_function(function () { $e = error_get_last(); if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) echo json_encode(['fatal' => $e['message'], 'line' => $e['line']]); });
echo json_encode(['sodium' => extension_loaded('sodium'), 'php' => PHP_VERSION]) . "\n";
// After 3.33.57: (1) the public MCP server answers at https://dar.ink/mcp; (2) Dar is listed in the official MCP Registry
// (registry.modelcontextprotocol.io) under ink.dar/real-estate-tools — proved by the domain itself: an Ed25519 key whose public half
// is served at /.well-known/mcp-registry-auth (the private half stays in the site's database). Prints statuses only, never a key.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION]; $H = 'https://dar.ink';
$se = log_tail(data_dir() . '/server-errors.log', 7200); $o['server_errors_2h'] = array_map(fn($g) => mb_substr((string)$g['msg'], 0, 160) . ' @' . ($g['where'] ?? '') . ' x' . ($g['n'] ?? ''), $se['groups'] ?? []);
$post = function (string $u, $body, array $h = []): array { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 40, CURLOPT_POST => true, CURLOPT_POSTFIELDS => is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json', 'Accept: application/json, text/event-stream'], $h)]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s, $b]; };
// (1) the server, as an agent sees it
[$s, $b] = $post($H . '/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => ['protocolVersion' => '2025-06-18', 'capabilities' => new stdClass(), 'clientInfo' => ['name' => 'dar-check', 'version' => '1']]]); $j = json_decode($b, true); $o['mcp_init'] = [$s, $j['result']['serverInfo']['name'] ?? substr($b, 0, 120)];
[$s, $b] = $post($H . '/mcp', ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/call', 'params' => ['name' => 'commission_calculator', 'arguments' => ['value' => 1000000, 'pct' => 2]]]); $j = json_decode($b, true); $o['mcp_call'] = [$s, $j['result']['structuredContent']['commission'] ?? substr($b, 0, 120)];
$c = curl_init($H . '/.well-known/mcp/server.json'); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]); $card = (string)curl_exec($c); $o['card'] = [curl_getinfo($c, CURLINFO_HTTP_CODE), json_decode($card, true)['name'] ?? '']; curl_close($c);
// (2) the registry: the domain's key (made once), its public half where the registry reads it
$sk = (string)(meta_get('mcp_reg_sk') ?? ''); if ($sk === '' || strlen(base64_decode($sk)) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) { $kp = sodium_crypto_sign_keypair(); $sk = base64_encode(sodium_crypto_sign_secretkey($kp)); meta_set('mcp_reg_sk', $sk); }
$skb = base64_decode($sk); $pk = sodium_crypto_sign_publickey_from_secretkey($skb);
@mkdir($R . '/.well-known', 0755, true); file_put_contents($R . '/.well-known/mcp-registry-auth', 'v=MCPv1; k=ed25519; p=' . base64_encode($pk) . "\n");
$c = curl_init($H . '/.well-known/mcp-registry-auth'); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]); $wk = (string)curl_exec($c); $o['auth_file'] = [curl_getinfo($c, CURLINFO_HTTP_CODE), str_starts_with($wk, 'v=MCPv1; k=ed25519; p=')]; curl_close($c);
$ts = gmdate('Y-m-d\TH:i:s\Z'); $sig = bin2hex(sodium_crypto_sign_detached($ts, $skb));
[$s, $b] = $post('https://registry.modelcontextprotocol.io/v0/auth/http', ['domain' => 'dar.ink', 'timestamp' => $ts, 'signed_timestamp' => $sig]); $j = json_decode($b, true); $tok = (string)($j['registry_token'] ?? ''); $o['registry_auth'] = [$s, $tok !== '' ? 'token' : substr($b, 0, 300)];
if ($tok !== '') { [$s, $b] = $post('https://registry.modelcontextprotocol.io/v0/publish', $card, ['Authorization: Bearer ' . $tok]); $o['registry_publish'] = [$s, substr($b, 0, 400)]; }
$c = curl_init('https://registry.modelcontextprotocol.io/v0/servers?search=ink.dar'); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]); $b = (string)curl_exec($c); $o['registry_search'] = [curl_getinfo($c, CURLINFO_HTTP_CODE), substr($b, 0, 300)]; curl_close($c);
$o['mcp_use_today'] = jdec((string)(meta_get('mcp:' . gmdate('Y-m-d')) ?? ''));
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
