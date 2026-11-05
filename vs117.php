<?php
// Why a drawing is not kept: one SVG asked of each lane alone (Gemini, Groq, Mistral) with the studio's own prompt — time, length, how
// the answer ends, what the cleaning says — and the AI log lines of these asks (their codes)
ini_set('display_errors', '0'); @set_time_limit(700);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION]; $c = grow_owner(); $o['dev'] = !empty($c['dev']); $t0 = now_ms();
$look = ['brand' => '#1f3a2e', 'accent' => '#c8a24a', 'bg' => '#f7f3ec', 'ink' => '#1a1a1a', 'mode' => 'light', 'arch' => 'contemporary Najdi villas, Riyadh'];
foreach (['gemini', 'groq', 'mistral'] as $only) {
    $t = microtime(true);
    try { $r = ai_complete($c, ['messages' => [['role' => 'system', 'content' => art_sys('wide', $look)], ['role' => 'user', 'content' => "Draw: contemporary Najdi villa facade with palm trees at golden hour, north Riyadh\nShape: wide (1600×900)."]], 'tier' => 'standard', 'only' => $only, 'temperature' => 0.7, 'max_tokens' => 10000, 'deadline' => 150, 'cache' => false]);
        $tx = (string)($r['text'] ?? ''); $cl = art_clean($tx, 'wide');
        $o['lane'][$only] = ['by' => ($r['provider'] ?? '') . '/' . ($r['model'] ?? ''), 's' => round(microtime(true) - $t, 1), 'len' => strlen($tx), 'close' => str_contains($tx, '</svg>'), 'shapes' => preg_match_all('/<(path|rect|circle|ellipse|polygon|polyline|line)\b/i', $tx), 'clean' => $cl === null ? 'null' : strlen($cl), 'weak' => $r['weak'] ?? '', 'head' => mb_substr($tx, 0, 60), 'tail' => mb_substr($tx, -60)];
        if ($cl !== null) $o['svg'][$only] = base64_encode($cl);
    } catch (Throwable $e) { $o['lane'][$only] = ['err' => substr($e->getMessage(), 0, 160), 's' => round(microtime(true) - $t, 1)]; }
}
$o['log'] = array_map(fn($r) => implode(' ', [$r['provider'], $r['model'], $r['ok'], $r['ms'], mb_substr((string)$r['code'], 0, 90)]), q('SELECT * FROM ai_log WHERE at >= ? ORDER BY id', [$t0])->fetchAll());
echo json_encode($o, JSON_UNESCAPED_SLASHES), "\n";
