<?php
// After 3.33.64, part two: the rest of the gallery's examples made by the team; the gallery, the sitemap
ini_set('display_errors', '1'); error_reporting(E_ALL); @set_time_limit(1800);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION]; $H = 'https://dar.ink';
$get = function (string $u): array { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s, $b]; };
$o['examples'] = isite_ex_make(5);
[$s, $b] = $get($H . '/free/website/examples/'); $o['gallery'] = [$s, substr_count($b, 'class="card"')];
foreach (ISITE_EX as $x) if (($r = isite_ex_rec($x[0]))) { [$s] = $get($H . '/free/website/examples/' . $x[0] . '/'); $o['ex'][$x[0]] = [$s, $r['by'] ?? '', $r['team']['direction'] ?? '', substr_count(json_encode($r['d']), 'data-ai-img')]; }
[$s, $b] = $get($H . '/sitemap.xml'); $o['sitemap'] = [$s, substr_count($b, '/free/website/examples/')];
$o['health'] = jdec((string)(meta_get('health') ?? ''))->reasons ?? null;
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
