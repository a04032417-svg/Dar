<?php
// Publish Dar's MCP server in the official MCP Registry (the domain key made by the last run; a description within the registry's 100 characters)
ini_set('display_errors', '1'); error_reporting(E_ALL);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = [];
$post = function (string $u, $body, array $h = []): array { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 40, CURLOPT_POST => true, CURLOPT_POSTFIELDS => is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $h)]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s, $b]; };
$c = curl_init('https://dar.ink/.well-known/mcp/server.json'); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]); $card = json_decode((string)curl_exec($c), true); curl_close($c);
$card['description'] = 'Real-estate tools: commission, lease, Saudi FAL licence check, AI listings, shareable listing pages.';
$skb = base64_decode((string)(meta_get('mcp_reg_sk') ?? '')); $o['key'] = strlen($skb) === SODIUM_CRYPTO_SIGN_SECRETKEYBYTES;
$ts = gmdate('Y-m-d\TH:i:s\Z'); $sig = bin2hex(sodium_crypto_sign_detached($ts, $skb));
[$s, $b] = $post('https://registry.modelcontextprotocol.io/v0/auth/http', ['domain' => 'dar.ink', 'timestamp' => $ts, 'signed_timestamp' => $sig]); $tok = (string)(json_decode($b, true)['registry_token'] ?? ''); $o['auth'] = [$s, $tok !== ''];
if ($tok !== '') { [$s, $b] = $post('https://registry.modelcontextprotocol.io/v0/publish', $card, ['Authorization: Bearer ' . $tok]); $o['publish'] = [$s, substr($b, 0, 600)]; }
$c = curl_init('https://registry.modelcontextprotocol.io/v0/servers?search=ink.dar'); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]); $o['search'] = substr((string)curl_exec($c), 0, 600); curl_close($c);
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
