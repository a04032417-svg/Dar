<?php
// Where free AI capacity is right now: for each key of each provider, its models (as the provider lists them) and a one-word answer
// from several models — which answer, which are out of their allowance (per model: the allowances are counted per model). No secret printed.
ini_set('display_errors', '0'); @set_time_limit(600);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$cfg = ai_cfg(); $o = ['ver' => SYNC_VERSION];
$ask = function (string $id, string $key, string $model) use ($cfg): string {
    $base = ai_base($cfg, $id); $t = microtime(true);
    if ($id === 'gemini') { [$code, $res] = ai_http('POST', $base . '/models/' . rawurlencode($model) . ':generateContent', array_merge(['Content-Type: application/json'], ai_auth_headers('gemini', $key)), jenc(['contents' => [['role' => 'user', 'parts' => [['text' => 'Say OK']]]], 'generationConfig' => ['maxOutputTokens' => 20]]), 20); }
    else { [$code, $res] = ai_http('POST', rtrim($base, '/') . '/chat/completions', array_merge(['Content-Type: application/json'], ai_auth_headers($id, $key)), jenc(['model' => $model, 'messages' => [['role' => 'user', 'content' => 'Say OK']], 'max_tokens' => 20]), 20); }
    $j = json_decode((string)$res, true); $msg = (string)($j['error']['message'] ?? ($j['message'] ?? ($j['detail'] ?? ''))); if (is_array($j['error'] ?? null) === false && is_string($j['error'] ?? null)) $msg = $j['error'];
    return $code . ' ' . round(microtime(true) - $t, 1) . 's' . ($code === 200 ? '' : ' ' . mb_substr(preg_replace('/\s+/', ' ', $msg), 0, 90));
};
foreach (['gemini', 'groq', 'cerebras', 'mistral', 'huggingface', 'openrouter', 'sambanova', 'nvidia', 'together', 'fireworks', 'deepinfra', 'cohere', 'zai', 'alibaba', 'hyperbolic', 'deepseek', 'moonshot', 'xai', 'openai', 'anthropic'] as $id) {
    $keys = ai_keys($id); if (!$keys) continue; $base = ai_base($cfg, $id); $P = ['keys' => count($keys), 'enabled' => !empty($cfg['providers'][$id]['enabled'])];
    $key0 = (string)(vault_get($keys[0]) ?? '');
    // its models
    if ($id === 'gemini') { [$c, $r] = ai_http('GET', $base . '/models?pageSize=1000', ai_auth_headers('gemini', $key0), null, 15); $ms = array_map(fn($m) => preg_replace('#^models/#', '', (string)($m['name'] ?? '')), array_filter(json_decode((string)$r, true)['models'] ?? [], fn($m) => in_array('generateContent', $m['supportedGenerationMethods'] ?? [], true))); }
    else { [$c, $r] = ai_http('GET', rtrim($base, '/') . '/models', ai_auth_headers($id, $key0), null, 15); $ms = array_map(fn($m) => (string)($m['id'] ?? ''), json_decode((string)$r, true)['data'] ?? []); }
    $ms = array_values(array_filter($ms, fn($m) => $m !== '' && !preg_match('/embed|whisper|tts|audio|image|vision-only|guard|moderation|rerank|transcri|speech|imagen|veo|lyria|aqa|robotics|computer-use|native-audio|live/i', $m)));
    usort($ms, fn($a, $b) => [preg_match('/gemma-3-27b|gemma-3n|flash-lite|gpt-oss-120b|qwen-3-235b|llama-3.3-70b|kimi|deepseek|mistral-small|:free/i', $a) ? 0 : 1, $b] <=> [preg_match('/gemma-3-27b|gemma-3n|flash-lite|gpt-oss-120b|qwen-3-235b|llama-3.3-70b|kimi|deepseek|mistral-small|:free/i', $b) ? 0 : 1, $a]);
    $P['models_http'] = $c; $P['models'] = array_slice($ms, 0, 40);
    // which models answer now (first key: up to 8 models; then each other key on the provider's first answering model)
    $tried = 0; $ok = [];
    foreach ($ms as $m) { if ($tried >= ($id === 'gemini' ? 14 : 8)) break; if ($id === 'openrouter' && !str_ends_with($m, ':free') && $m !== 'openrouter/free') continue; $tried++; $res = $ask($id, $key0, $m); $P['try'][$m] = $res; if (str_starts_with($res, '200')) $ok[] = $m; }
    if ($ok && count($keys) > 1) foreach (array_slice($keys, 1) as $i => $kn) $P['other_keys'][] = $ask($id, (string)(vault_get($kn) ?? ''), $ok[0]);
    $o['p'][$id] = $P;
}
echo json_encode($o, JSON_UNESCAPED_SLASHES), "\n";
