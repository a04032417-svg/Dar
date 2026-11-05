<?php
// After 3.33.71: two pictures drawn live by the AI exactly as a site's are (the lanes that answer now), as they are kept; the gallery's
// pictures waiting for the cron job; the last AI calls
ini_set('display_errors', '0'); @set_time_limit(400);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION]; $c = grow_owner(); $look = ['brand' => '#1f3a2e', 'accent' => '#c8a24a', 'bg' => '#f7f3ec', 'ink' => '#1a1a1a', 'mode' => 'light', 'arch' => 'contemporary Najdi villas, Riyadh'];
foreach ([['wide', 'contemporary Najdi villa facade with palm trees at golden hour, north Riyadh'], ['square', 'bright modern apartment living room with floor-to-ceiling windows, Riyadh']] as [$sz, $p]) {
    $t = microtime(true); $svg = art_draw($c, $p, $sz, $look, 110);
    $last = one('SELECT provider, model FROM ai_log WHERE ok = 1 ORDER BY id DESC LIMIT 1');
    $o['draw'][] = ['sz' => $sz, 's' => round(microtime(true) - $t, 1), 'len' => strlen($svg), 'shapes' => preg_match_all('/<(path|rect|circle|ellipse|polygon|polyline|line)\b/i', $svg), 'by' => ($last['provider'] ?? '') . '/' . ($last['model'] ?? '')];
    if ($svg !== '') $o['svg'][$sz] = base64_encode($svg);
}
$left = 0; foreach (q("SELECT v FROM meta WHERE k LIKE 'gx:%'")->fetchAll() as $row) $left += preg_match_all('/\{\{\s*img\b/', (string)$row['v']); $o['gallery_left'] = $left; $o['mig2'] = meta_get('art_mig2') !== null;
$o['ai'] = array_map(fn($r) => implode(' ', [round((now_ms() - (int)$r['at']) / 1000) . 's', $r['provider'], $r['model'], $r['ok'], $r['ms'], mb_substr((string)$r['code'], 0, 60)]), q('SELECT * FROM ai_log WHERE at > ? ORDER BY id DESC LIMIT 14', [now_ms() - 400000])->fetchAll());
echo json_encode($o, JSON_UNESCAPED_SLASHES), "\n";
