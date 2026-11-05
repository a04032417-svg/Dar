<?php
// Why the live site's pictures were not drawn: the AI asked as the studio asks (raw answer: who, how long, how much, how it ends; what
// the cleaning says), and the AI log of the last minutes
ini_set('display_errors', '0'); @set_time_limit(400);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION]; $c = grow_owner(); $look = ['brand' => '#1f3a2e', 'accent' => '#c8a24a', 'bg' => '#f7f3ec', 'ink' => '#1a1a1a', 'mode' => 'light', 'arch' => 'contemporary Najdi villas, Riyadh'];
foreach ([['wide', 'contemporary Najdi villa facade with palm trees at golden hour, north Riyadh', 10000, 55], ['square', 'bright modern apartment living room with large windows', 7000, 55]] as [$sz, $p, $mt, $dl]) {
    $t = microtime(true);
    try { $r = ai_complete($c, ['messages' => [['role' => 'system', 'content' => art_sys($sz, $look)], ['role' => 'user', 'content' => 'Draw: ' . $p . "\nShape: " . $sz]], 'tier' => 'standard', 'lead' => STUDIO_LEAD, 'avoid' => STUDIO_AVOID, 'temperature' => 0.7, 'max_tokens' => $mt, 'deadline' => $dl, 'cache' => false]);
        $tx = (string)($r['text'] ?? ''); $cl = art_clean($tx, $sz); $doc = null;
        $o['try'][] = ['sz' => $sz, 'by' => ($r['provider'] ?? '') . '/' . ($r['model'] ?? ''), 's' => round(microtime(true) - $t, 1), 'len' => strlen($tx), 'head' => mb_substr($tx, 0, 80), 'tail' => mb_substr($tx, -80), 'has_close' => str_contains($tx, '</svg>'), 'shapes' => preg_match_all('/<(path|rect|circle|ellipse|polygon|polyline|line)\b/i', $tx), 'clean' => $cl === null ? 'null' : strlen($cl), 'finish' => $r['finish'] ?? ($r['stop'] ?? '')];
        if ($cl !== null) $o['svg_' . $sz] = base64_encode($cl);
    } catch (Throwable $e) { $o['try'][] = ['sz' => $sz, 'err' => substr($e->getMessage(), 0, 200), 's' => round(microtime(true) - $t, 1)]; }
}
$o['ai'] = array_map(fn($r) => implode(' ', [round((now_ms() - (int)$r['at']) / 1000) . 's', $r['provider'], $r['model'], $r['ok'], $r['ms'], mb_substr((string)$r['code'], 0, 80)]), q('SELECT * FROM ai_log WHERE at > ? ORDER BY id DESC LIMIT 30', [now_ms() - 900000])->fetchAll());
echo json_encode($o, JSON_UNESCAPED_SLASHES), "\n";
