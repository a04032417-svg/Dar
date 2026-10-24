<?php
// Dar — after 3.33.15: the pricing engine's web search for the test office in its own country (Kuwait), once, and what the AI wrote back
require '/home/u791571885/domains/dar.ink/public_html/dar-sync.php';
$u = one("SELECT id FROM users WHERE tenant = 'tmvlc7zhwu5pu8w' AND role = 'owner' LIMIT 1"); $c = acct_ctx_of((string)$u['id']); $o = ['country' => (string)(tenant_obj($c['trow'])->country ?? '')];
$t = microtime(true);
try { $r = op_market_web($c, ['city' => 'الكويت', 'district' => 'السالمية', 'type' => 'apartment', 'purpose' => 'sale', 'area' => 120, 'bedrooms' => 2]); $o['web'] = ['n' => count($r['listings'] ?? []), 'by' => $r['by'] ?? '', 'sources' => count($r['sources'] ?? []), 'first' => array_slice(array_map(fn($x) => [$x['price'], $x['area'], $x['site']], $r['listings'] ?? []), 0, 4), 'summary' => mb_substr((string)($r['summary'] ?? ''), 0, 220)]; } catch (Throwable $e) { $o['web'] = ['fail' => $e->getMessage()]; }
$o['ms'] = (int)((microtime(true) - $t) * 1000);
// the raw answer for the same kind of question (to see its shape)
$sys = 'You are a real-estate market researcher. Search the web now for CURRENT asking prices of: apartments for sale, about 120 m², 2 bedrooms in Salmiya, Kuwait City, Kuwait. Report only listings you actually found. Prices as plain numbers in KWD, areas in square metres. Answer with one JSON object only: {"listings":[{"title":"","price":0,"area":0,"district":"","url":"https://…","site":"","date":"","note":""}],"summary":"two short sentences in Arabic"}';
$r2 = ai_complete($c, ['messages' => [['role' => 'system', 'content' => $sys], ['role' => 'user', 'content' => 'Search and answer.']], 'web' => true, 'json' => true, 'soft_json' => true, 'tier' => 'economy', 'temperature' => 0.1, 'max_tokens' => 2500, 'fn' => 'market.web', 'cache' => false]);
$tx = (string)($r2['text'] ?? ''); $o['raw'] = ['provider' => $r2['provider'] ?? '', 'data' => is_array($r2['data'] ?? null) ? array_keys($r2['data']) : null, 'len' => mb_strlen($tx), 'head' => mb_substr($tx, 0, 500), 'tail' => mb_substr($tx, -400)];
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
