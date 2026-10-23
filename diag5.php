<?php
// Dar — read-only (nothing is changed; a few short AI calls): which models can search the web for «رادار السوق» — Groq's list of models
// (its search models), Gemini's flash models with Google Search, Anthropic's web search tool.
require '/home/u791571885/domains/dar.ink/public_html/dar-sync.php';
$cfg = ai_cfg(); $o = [];
$k = fn($id) => ($s = ai_slots($cfg, $id)) ? (str_starts_with($s[0], 'open:') ? '' : (string)vault_get($s[0])) : '';
[$c, $r, $e] = ai_http('GET', 'https://api.groq.com/openai/v1/models', ['Authorization: Bearer ' . $k('groq')], null, 20);
$j = json_decode((string)$r, true); $o['groq_models'] = array_values(array_map(fn($m) => $m['id'], array_filter((array)($j['data'] ?? []), fn($m) => is_array($m) && preg_match('/compound|search|browse|online/i', (string)($m['id'] ?? ''))))); $o['groq_http'] = $c;
[$c, $r, $e] = ai_http('GET', 'https://generativelanguage.googleapis.com/v1beta/models?pageSize=200', ['x-goog-api-key: ' . $k('gemini')], null, 20);
$j = json_decode((string)$r, true); $o['gemini_models'] = array_values(array_map(fn($m) => preg_replace('#^models/#', '', $m['name']), array_filter((array)($j['models'] ?? []), fn($m) => is_array($m) && preg_match('/flash/i', (string)($m['name'] ?? '')) && in_array('generateContent', (array)($m['supportedGenerationMethods'] ?? []), true))));
$msgs = [['role' => 'system', 'content' => 'Search the web now for one current listing of an apartment for sale in Riyadh. Answer with one JSON object only: {"title":"","price":0,"url":""}'], ['role' => 'user', 'content' => 'Search and answer.']];
$try = function (string $id, string $model) use ($cfg, $k, $msgs) { $t = microtime(true); try { $r = ai_try($cfg, $id, $k($id), $model, $msgs, ['web' => true, 'json' => true, 'max_tokens' => 600, 'temperature' => 0.1], 40); return ['code' => $r['code'] ?? null, 'err' => mb_substr((string)($r['err'] ?? ''), 0, 200), 'text' => mb_substr((string)($r['text'] ?? ''), 0, 160), 'ms' => (int)((microtime(true) - $t) * 1000)]; } catch (Throwable $e) { return ['ex' => $e->getMessage()]; } };
foreach (array_slice(array_values(array_filter($o['gemini_models'], fn($m) => preg_match('/^gemini-(2\.5|3\.\d)-flash(-lite)?$/', $m))), 0, 4) as $m) $o['gemini_web'][$m] = $try('gemini', $m);
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
