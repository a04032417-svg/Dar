<?php
// Dar — after 3.33.16: the pricing engine's web search for the test office, once (Hawalli, Kuwait — its own country), summed up
require '/home/u791571885/domains/dar.ink/public_html/dar-sync.php';
$u = one("SELECT id FROM users WHERE tenant = 'tmvlc7zhwu5pu8w' AND role = 'owner' LIMIT 1"); $c = acct_ctx_of((string)$u['id']); $o = ['v' => SYNC_VERSION]; $t = microtime(true);
try { $r = op_market_web($c, ['city' => 'الكويت', 'district' => 'حولي', 'type' => 'apartment', 'purpose' => 'sale', 'area' => 120, 'bedrooms' => 2]); $o['web'] = ['n' => count($r['listings'] ?? []), 'by' => $r['by'] ?? '', 'sources' => count($r['sources'] ?? []), 'first' => array_slice(array_map(fn($x) => [$x['price'], $x['area'], $x['site'], $x['district']], $r['listings'] ?? []), 0, 6), 'summary' => mb_substr((string)($r['summary'] ?? ''), 0, 240)]; } catch (Throwable $e) { $o['web'] = ['fail' => $e->getMessage()]; }
$o['ms'] = (int)((microtime(true) - $t) * 1000); echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
