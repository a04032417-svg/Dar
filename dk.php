<?php
// Dar — read-only (nothing is changed; one short request per key, no key is ever printed): every AI key the platform holds — how many
// each provider has and its state for ordinary answers; and for Gemini and Groq, each key asked the same small web search
require '/home/u791571885/domains/dar.ink/public_html/dar-sync.php';
$cfg = ai_cfg(); $o = ['keys' => [], 'web' => []]; $now = time();
foreach (array_keys(AI_PRESETS) as $id) { $sl = ai_keys($id); if (!$sl) continue; $o['keys'][$id] = array_map(function ($s) use ($cfg, $now) { $st = ai_key_state($cfg, $s); return ['rest_min' => max(0, (int)ceil(((int)($st['rest_until'] ?? 0) - $now) / 60)), 'err' => (string)($st['err'] ?? ''), 'ok_ago_min' => ($st['ok_at'] ?? 0) ? (int)floor(($now - (int)$st['ok_at']) / 60) : null]; }, $sl); }
$msgs = [['role' => 'system', 'content' => 'Search the web now for one current listing of an apartment for sale in Kuwait City. Answer with one JSON object only: {"title":"","price":0,"url":""}'], ['role' => 'user', 'content' => 'Search and answer.']];
foreach (['gemini', 'groq'] as $id) foreach (ai_keys($id) as $i => $slot) {
    $key = (string)vault_get($slot); $model = ai_model($cfg, $id, 'economy');
    try { $r = ai_try($cfg, $id, $key, $model, $msgs, ['web' => true, 'json' => true, 'max_tokens' => 500, 'temperature' => 0.1], 30); $o['web'][$id][] = [$i + 1, $r['code'] ?? null, mb_substr((string)($r['err'] ?? ''), 0, 110), mb_strlen((string)($r['text'] ?? '')) > 0]; }
    catch (Throwable $e) { $o['web'][$id][] = [$i + 1, 'ex', mb_substr($e->getMessage(), 0, 110)]; }
}
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
