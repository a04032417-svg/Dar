<?php
// Dar — after installing 3.33.19 (reads; one web search for the pricing engine is asked, as an office's would be): the version, the
// bank-transfer switch (yes/no only), the translation's share and its refused texts, WhatsApp's gateway, the pricing engine's search.
$R = '/home/u791571885/domains/dar.ink/public_html'; require $R . '/dar-sync.php';
$tq = function (string $sql, array $a = []) { try { return q($sql, $a)->fetchAll(); } catch (Throwable $e) { return 'ERR ' . $e->getMessage(); } };
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c'), 'mig3319' => meta_get('mig_v3319'), 'lang3319' => meta_get('lang_v3319')];
$b = bill_cfg(); $o['bank'] = ['enabled' => $b['bank']['enabled'], 'details' => $b['bank']['iban'] !== '' && $b['bank']['beneficiary'] !== '', 'ready' => bill_bank_ready($b)];
$o['whop_ready'] = function_exists('whop_ready') ? whop_ready() : null;
$o['lang'] = ['held' => lang_bg_held(), 'used_today' => lang_bg_used(), 'failed' => $tq("SELECT err, COUNT(*) n FROM tm_q WHERE tries >= 3 GROUP BY err"), 'waiting' => $tq("SELECT COUNT(*) n FROM tm_q WHERE tries < 3")];
$h = function_exists('wag_health') ? wag_health() : null; $o['wa'] = is_array($h) ? ['ok' => !empty($h['ok']), 'v' => $h['version'] ?? null] : null;
$o['incidents'] = $tq("SELECT code, level, status FROM doc_inc WHERE status IN ('open', 'stuck') ORDER BY rowid DESC LIMIT 12");
$c = doc_owner(); $t0 = microtime(true);
try { $r = ai_complete($c, ['messages' => [['role' => 'system', 'content' => 'You search the web for real-estate listings and answer in JSON: {"listings":[{"title":"","price":0,"area":0,"url":""}],"note":""}'], ['role' => 'user', 'content' => 'Apartments for sale in Salmiya, Kuwait: find 6 current listings with price (KWD) and area (m2).']], 'web' => true, 'tier' => 'economy', 'json' => true, 'soft_json' => true, 'max_tokens' => 1500, 'fn' => 'market.web.check']);
  $j = $r['data'] ?? json_decode((string)preg_replace('/^```(?:json)?|```$/m', '', (string)($r['text'] ?? '')), true);
  $o['web'] = ['ok' => true, 'provider' => $r['provider'] ?? '', 'model' => $r['model'] ?? '', 'ms' => (int)round((microtime(true) - $t0) * 1000), 'listings' => is_array($j['listings'] ?? null) ? count($j['listings']) : null, 'sources' => is_array($r['sources'] ?? null) ? count($r['sources']) : null]; }
catch (Throwable $e) { $o['web'] = ['ok' => false, 'err' => $e->getMessage(), 'ms' => (int)round((microtime(true) - $t0) * 1000), 'why' => $e instanceof DarFail ? ($e->extra ?? null) : null]; }
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
