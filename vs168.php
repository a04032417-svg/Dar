<?php
// After 3.33.103: (1) the offices the daily search left out under 3.33.102 only because the AI called them an «agency» or a «brokerage»
// (fit 6 or more) are judged again under 3.33.103's rule; (2) why the WhatsApp agent's internet look-ups fail — which search models
// the platform's own Groq and Gemini keys reach now (one tiny question each; nothing is sent to anybody). Keys are never printed.
ini_set('display_errors', '0'); @set_time_limit(0); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'at' => gmdate('c')];
$cut = strtotime('2026-11-06 10:27:00 UTC') * 1000; $n = 0;
foreach (q("SELECT id, reason, fit, chk FROM pr_leads WHERE st = 'skip' AND fit >= 6 AND reason LIKE 'fit:%' AND chk < ?", [$cut])->fetchAll() as $r) { q("UPDATE pr_leads SET st = 'new', tries = 0, reason = '' WHERE id = ?", [$r['id']]); $n++; }
$o['requeued'] = $n;
$key = function (string $id): string { foreach (ai_keys($id) as $nm) { $v = vault_get($nm); if ($v) return $v; } return ''; };
$g = $key('groq');
if ($g !== '') {
    [$c, $e, $b] = http_out('GET', 'https://api.groq.com/openai/v1/models', ['Authorization: Bearer ' . $g], null, 20, 400000);
    $ids = array_column((array)(json_decode((string)$b, true)['data'] ?? []), 'id'); $o['groq_models'] = ['code' => $c, 'search' => array_values(array_filter($ids, fn($x) => preg_match('/compound|search|browser|gpt-oss/i', $x)))];
    foreach ([['groq/compound-mini', null], ['groq/compound', null], ['openai/gpt-oss-120b', [['type' => 'browser_search']]]] as [$m, $tools]) {
        $body = ['model' => $m, 'messages' => [['role' => 'user', 'content' => 'What is today\'s date? One line.']], 'max_tokens' => 300] + ($tools ? ['tools' => $tools] : []);
        $t0 = microtime(true); [$c, $e, $b] = http_out('POST', 'https://api.groq.com/openai/v1/chat/completions', ['Content-Type: application/json', 'Authorization: Bearer ' . $g], jenc($body), 40, 400000);
        $j = json_decode((string)$b, true); $o['groq_try'][$m] = ['code' => $c, 's' => round(microtime(true) - $t0, 1), 'say' => mb_substr((string)($j['choices'][0]['message']['content'] ?? ($j['error']['message'] ?? $e)), 0, 160)];
    }
}
$k = $key('gemini');
if ($k !== '') foreach (['gemini-2.5-flash', 'gemini-2.5-flash-lite', 'gemini-2.0-flash'] as $m) {
    $body = ['contents' => [['role' => 'user', 'parts' => [['text' => 'What is today\'s date? One line.']]]], 'tools' => [['google_search' => new stdClass()]]];
    $t0 = microtime(true); [$c, $e, $b] = http_out('POST', 'https://generativelanguage.googleapis.com/v1beta/models/' . $m . ':generateContent', ['Content-Type: application/json', 'x-goog-api-key: ' . $k], jenc($body), 40, 400000);
    $j = json_decode((string)$b, true); $o['gemini_try'][$m] = ['code' => $c, 's' => round(microtime(true) - $t0, 1), 'say' => mb_substr((string)($j['candidates'][0]['content']['parts'][0]['text'] ?? ($j['error']['message'] ?? $e)), 0, 160)];
}
$o['gemini_keys'] = count(ai_keys('gemini')); $o['groq_keys'] = count(ai_keys('groq'));
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR), "\n";
