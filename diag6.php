<?php
// Dar — read-only (nothing is changed; two short AI calls): can the owner's Anthropic key or OpenRouter key search the web for «رادار السوق»
require '/home/u791571885/domains/dar.ink/public_html/dar-sync.php';
$cfg = ai_cfg(); $o = [];
$k = fn($id) => ($s = ai_slots($cfg, $id)) ? (str_starts_with($s[0], 'open:') ? '' : (string)vault_get($s[0])) : '';
$q = 'Search the web now for one current listing of an apartment for sale in Riyadh, Saudi Arabia. Answer with one JSON object only: {"title":"","price":0,"area":0,"url":""}';
foreach (['anthropic', 'openrouter'] as $id) $o['model_' . $id] = [ai_model($cfg, $id, 'standard'), ai_model($cfg, $id, 'economy')];
$t = microtime(true);
[$c, $r, $e] = ai_http('POST', rtrim(ai_base($cfg, 'anthropic'), '/') . '/messages', ['Content-Type: application/json', 'x-api-key: ' . $k('anthropic'), 'anthropic-version: 2023-06-01'], jenc(['model' => ai_model($cfg, 'anthropic', 'economy'), 'max_tokens' => 900, 'messages' => [['role' => 'user', 'content' => $q]], 'tools' => [['type' => 'web_search_20250305', 'name' => 'web_search', 'max_uses' => 2]]]), 60);
$j = json_decode((string)$r, true); $txt = ''; $src = [];
foreach ((array)($j['content'] ?? []) as $b) { if (($b['type'] ?? '') === 'text') { $txt .= (string)($b['text'] ?? ''); foreach ((array)($b['citations'] ?? []) as $ci) $src[] = $ci['url'] ?? ''; } if (($b['type'] ?? '') === 'web_search_tool_result') foreach ((array)($b['content'] ?? []) as $w) if (is_array($w) && isset($w['url'])) $src[] = $w['url']; }
$o['anthropic'] = ['http' => $c, 'err' => mb_substr((string)($j['error']['message'] ?? $e), 0, 200), 'text' => mb_substr($txt, 0, 300), 'sources' => array_slice(array_values(array_unique(array_filter($src))), 0, 5), 'usage' => $j['usage'] ?? null, 'ms' => (int)((microtime(true) - $t) * 1000)];
$t = microtime(true);
[$c, $r, $e] = ai_http('POST', rtrim(ai_base($cfg, 'openrouter'), '/') . '/chat/completions', ['Content-Type: application/json', 'Authorization: Bearer ' . $k('openrouter')], jenc(['model' => ai_model($cfg, 'openrouter', 'economy'), 'max_tokens' => 700, 'messages' => [['role' => 'user', 'content' => $q]], 'plugins' => [['id' => 'web', 'max_results' => 3]]]), 60);
$j = json_decode((string)$r, true);
$o['openrouter'] = ['http' => $c, 'err' => mb_substr((string)($j['error']['message'] ?? $e), 0, 200), 'text' => mb_substr((string)($j['choices'][0]['message']['content'] ?? ''), 0, 300), 'ann' => array_slice(array_map(fn($a) => $a['url_citation']['url'] ?? '', (array)($j['choices'][0]['message']['annotations'] ?? [])), 0, 5), 'ms' => (int)((microtime(true) - $t) * 1000)];
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
