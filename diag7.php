<?php
// Dar — after 3.33.15: the pricing engine's web search asked once for the owner's test office (one real search, about two cents), and
// the answer summed up (how many listings, who searched, how many sources) — nothing else is changed (the answer is kept a day, as always)
require '/home/u791571885/domains/dar.ink/public_html/dar-sync.php';
$u = one("SELECT id FROM users WHERE tenant = 'tmvlc7zhwu5pu8w' AND role = 'owner' LIMIT 1"); $c = $u ? acct_ctx_of((string)$u['id']) : null;
$o = ['v' => SYNC_VERSION, 'ctx' => (bool)$c, 'web_rest' => o2a(jdec((string)(meta_get('ai_web_rest') ?? '')))];
if ($c) { $t = microtime(true); try { $r = op_market_web($c, ['city' => 'الرياض', 'district' => 'الملقا', 'type' => 'apartment', 'purpose' => 'sale', 'area' => 160, 'bedrooms' => 3]); $o['web'] = ['n' => count($r['listings'] ?? []), 'by' => $r['by'] ?? '', 'sources' => count($r['sources'] ?? []), 'first' => array_slice(array_map(fn($x) => [$x['price'], $x['area'], $x['site']], $r['listings'] ?? []), 0, 3), 'summary' => mb_substr((string)($r['summary'] ?? ''), 0, 200), 'cached' => $r['cached'] ?? null]; } catch (Throwable $e) { $o['web'] = ['fail' => $e->getMessage(), 'extra' => $GLOBALS['dar_fail_extra'] ?? null]; } $o['ms'] = (int)((microtime(true) - $t) * 1000); }
$o['web_rest_after'] = o2a(jdec((string)(meta_get('ai_web_rest') ?? '')));
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
