<?php
// Did any subscriber's website design (wj: jobs) take photographs from the photo bank since 3.33.67 was installed (05:15 UTC)? Counts only
ini_set('display_errors', '0'); @set_time_limit(120);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$bank = []; foreach (q('SELECT url FROM pbank')->fetchAll() as $r) $bank[(string)$r['url']] = 1;
$o = ['ver' => SYNC_VERSION, 'jobs' => []]; $t = 1793855700000;
foreach (q("SELECT k, v FROM meta WHERE k LIKE 'wj:%'")->fetchAll() as $row) {
    $r = o2a(jdec((string)$row['v'])); if (!is_array($r)) continue; $at = (int)($r['at'] ?? 0); if ($at < $t) continue;
    $all = json_encode($r['out'] ?? ($r['d'] ?? []), JSON_UNESCAPED_SLASHES); preg_match_all('/<img src=\\\\?"([^"\\\\]+)\\\\?"[^>]{0,40}data-ai-img/', $all, $m);
    $o['jobs'][] = ['st' => $r['st'] ?? '', 'kind' => $r['kind'] ?? '', 'mins_ago' => round((now_ms() - $at) / 60000), 'imgs' => count($m[1]), 'from_bank' => count(array_filter($m[1], fn($u) => isset($bank[$u]))), 'applied' => isset($r['applied']) ? 1 : 0];
}
$o['bank_uses'] = (int)one('SELECT COALESCE(SUM(uses), 0) n FROM pbank')['n'];
echo json_encode($o, JSON_UNESCAPED_SLASHES), "\n";
