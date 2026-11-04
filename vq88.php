<?php
// Clean-up after going back to 3.33.58: the requests board's leftover files and its data removed, and checked from outside
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION];
$rm = function (string $p) use (&$rm): void { if (is_dir($p)) { foreach (scandir($p) ?: [] as $f) if ($f !== '.' && $f !== '..') $rm($p . '/' . $f); @rmdir($p); } elseif (is_file($p)) @unlink($p); };
foreach (['free/requests', 'dar-demand.php'] as $p) { $had = file_exists($R . '/' . $p); $rm($R . '/' . $p); $o['removed'][$p] = [$had, !file_exists($R . '/' . $p)]; }
$o['rows_before'] = []; foreach (['demand', 'demand_reveal'] as $t) { try { $o['rows_before'][$t] = (int)(one("SELECT COUNT(*) n FROM $t")['n'] ?? 0); db()->exec("DROP TABLE IF EXISTS $t"); } catch (Throwable $e) { $o['rows_before'][$t] = 'none'; } }
$get = function (string $u, $post = null): array { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25] + ($post !== null ? [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($post), CURLOPT_HTTPHEADER => ['Content-Type: application/json']] : [])); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s, $b]; };
[$s] = $get('https://dar.ink/free/requests/'); $o['page_status'] = $s;
[$s, $b] = $get('https://dar.ink/dar-sync.php', ['op' => 'demand.board', 'payload' => new stdClass(), 'app' => 'site']); $o['board_op'] = json_decode($b, true)['error']['code'] ?? substr($b, 0, 80);
[$s, $b] = $get('https://dar.ink/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']); $o['mcp_tools'] = array_column(json_decode($b, true)['result']['tools'] ?? [], 'name');
[$s, $b] = $get('https://dar.ink/sitemap.xml'); $o['sitemap_requests'] = substr_count($b, '/free/requests/');
[$s, $b] = $get('https://dar.ink/llms.txt'); $o['llms_requests'] = substr_count($b, 'requests');
[$s, $b] = $get('https://registry.modelcontextprotocol.io/v0/servers?search=ink.dar'); $o['registry_desc'] = json_decode($b, true)['servers'][0]['server']['description'] ?? '';
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
