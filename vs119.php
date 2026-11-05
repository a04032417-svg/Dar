<?php
// The pictures of the live site mw3a4wbqe6ea21 as they are kept (to look at them) — read only
ini_set('display_errors', '0'); @set_time_limit(60);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$rec = o2a(jdec((string)meta_get('gs:mw3a4wbqe6ea21'))); $all = json_encode($rec['d'] ?? [], JSON_UNESCAPED_SLASHES); preg_match_all('/dar-sync\.php\?art=([0-9a-f]{32})/', $all, $am);
$o = ['n' => count(array_unique($am[1])), 'svgs' => []];
foreach (array_values(array_unique($am[1])) as $id) { $r = one('SELECT svg, w, h FROM art WHERE id = ?', [$id]); if ($r) $o['svgs'][] = ['w' => (int)$r['w'], 'h' => (int)$r['h'], 'b64' => base64_encode((string)$r['svg'])]; }
$o['css_cards'] = substr((string)($rec['d']['settings']['css'] ?? ''), (int)strpos((string)($rec['d']['settings']['css'] ?? ''), "this site's cards") - 3, 1500);
$o['pad'] = str_repeat('x', 80000);
echo json_encode($o, JSON_UNESCAPED_SLASHES), "\n";
