<?php
// Dar — read-only (nothing is changed; two short AI calls): why «رادار السوق»'s web lookup answered «ai_unavailable» — each web-capable
// provider asked the same small question with web search on, its answer's code and error (never a key).
require '/home/u791571885/domains/dar.ink/public_html/dar-sync.php';
$cfg = ai_cfg(); $o = ['ready' => ai_ready($cfg)];
$msgs = [['role' => 'system', 'content' => 'Search the web now for current asking prices of apartments for sale in Riyadh. Answer with one JSON object only: {"listings":[{"title":"","price":0,"url":""}]}'], ['role' => 'user', 'content' => 'Search and answer.']];
foreach (['gemini', 'groq'] as $id) {
    foreach (array_slice(ai_slots($cfg, $id), 0, 2) as $i => $slot) {
        $key = str_starts_with($slot, 'open:') ? '' : (string)vault_get($slot); $model = ai_model($cfg, $id, 'standard'); $t = microtime(true);
        try { $r = ai_try($cfg, $id, $key, $model, $msgs, ['web' => true, 'json' => true, 'max_tokens' => 800, 'temperature' => 0.1], 40); $o[$id . '#' . $i] = ['model' => $model, 'code' => $r['code'] ?? null, 'err' => mb_substr((string)($r['err'] ?? ''), 0, 300), 'text' => mb_substr((string)($r['text'] ?? ''), 0, 200), 'ms' => (int)((microtime(true) - $t) * 1000), 'state' => ai_key_state($cfg, $slot)]; }
        catch (Throwable $e) { $o[$id . '#' . $i] = ['ex' => $e->getMessage()]; }
    }
}
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
