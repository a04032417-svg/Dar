<?php
// Dar — read-only look at the live site (2026-10-29, after 3.33.31): the version and the market radar's own sources, asked as a visitor of the site asks them
// (one place in Riyadh and one in London: the counts the open map holds, the country's World Bank figures, a place searched by name) — numbers and timings only.
// What the radar asks is kept by the site's own place memory, as it keeps every such answer. Nothing else is changed.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
$ctx = ['user' => null, 'dev' => false, 'ip' => '127.0.0.1'];
$s = function (string $label, callable $f) use (&$o) { $t0 = microtime(true); try { $r = $f(); } catch (Throwable $e) { $r = 'ERR ' . substr($e->getMessage(), 0, 160); } $o[$label] = ['s' => round(microtime(true) - $t0, 1), 'r' => $r]; };
$market = fn(float $la, float $lo) => function () use ($ctx, $la, $lo) { $r = op_places($ctx, 'osm', ['kind' => 'market', 'lat' => $la, 'lon' => $lo, 'r' => 4000]); return isset($r['counts']) ? ['n' => count($r['counts']), 'sum' => array_sum(array_filter($r['counts'], 'is_numeric')), 'cached' => !empty($r['cached']), 'keys' => array_slice(array_keys($r['counts']), 0, 14)] : ['failed' => $r['failed'] ?? null, 'err' => $r['err'] ?? null]; };
$macro = fn(string $cc) => function () use ($ctx, $cc) { $r = op_places($ctx, 'macro', ['cc' => $cc]); $it = $r['items'] ?? []; return ['n' => is_array($it) ? count($it) : 0, 'failed' => $r['failed'] ?? false, 'err' => $r['err'] ?? null, 'sample' => is_array($it) ? array_slice(array_map(fn($x) => is_array($x) ? ($x['id'] ?? $x['k'] ?? '?') . '=' . json_encode($x['v'] ?? $x['value'] ?? null) . '@' . ($x['y'] ?? $x['year'] ?? '?') : '?', array_values($it)), 0, 3) : []]; };
$s('market_riyadh', $market(24.7136, 46.6753));
$s('market_london', $market(51.5074, -0.1278));
$s('macro_SA', $macro('SA'));
$s('macro_GB', $macro('GB'));
$s('geocode', function () use ($ctx) { $r = op_places($ctx, 'geocode', ['q' => 'الملقا الرياض', 'cc' => 'SA', 'lang' => 'ar']); $it = $r['items'] ?? []; return ['n' => count($it), 'first' => $it[0]['label'] ?? ($it[0]['name'] ?? ($it[0]['display_name'] ?? null)), 'lat' => $it[0]['lat'] ?? null]; });
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
