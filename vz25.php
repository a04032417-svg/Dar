<?php
// Dar — read-only look at the map and radar on the live site (2026-10-29): the errors the browsers and the server reported about them,
// the map sources' answers from this host, and the failed fetches the places cache keeps. Nothing is changed; no key, secret or name is printed.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php'; if (!function_exists('pl_cfg') && is_file($R . '/dar-places.php')) require_once $R . '/dar-places.php';
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
$s = function (string $label, callable $f) use (&$o) { try { $o[$label] = $f(); } catch (Throwable $e) { $o[$label] = 'ERR ' . substr($e->getMessage(), 0, 160); } };
$RX = '/map|radar|leaflet|tile|places|geocod|nearby|overpass|nominatim|photon|market|marker|bounds|latlng|\bL\.|ros_|office|district|feasib/i';
$tail = function (string $f, int $days) use ($RX) {
    if (!is_file($f)) return 'no log'; $sz = filesize($f); $fh = fopen($f, 'rb'); fseek($fh, max(0, $sz - 400000)); $t = (string)stream_get_contents($fh); fclose($fh);
    $g = []; $cut = time() - $days * 86400;
    foreach (explode("\n", $t) as $l) { $j = json_decode($l, true); if (!is_array($j)) continue; $at = strtotime((string)($j['t'] ?? $j['at'] ?? '')) ?: 0; if ($at && $at < $cut) continue;
        $m = (string)($j['msg'] ?? ''); $w = (string)($j['where'] ?? ''); $rt = (string)($j['route'] ?? ''); if (!preg_match($GLOBALS['RX'], $m . ' ' . $w . ' ' . $rt)) continue;
        $k = substr($m, 0, 140) . ' | ' . substr($w, 0, 80) . ' | ' . substr($rt, 0, 50) . ' | ' . ($j['v'] ?? ''); $g[$k] = ($g[$k] ?? 0) + (int)($j['count'] ?? 1); $g['~last ' . $k] = (string)($j['t'] ?? $j['at'] ?? ''); }
    $out = []; foreach ($g as $k => $n) if ($k[0] !== '~') $out[] = [$n, $k, $g['~last ' . $k] ?? '']; usort($out, fn($a, $b) => $b[0] <=> $a[0]); return array_slice($out, 0, 25); };
$GLOBALS['RX'] = $RX;
$s('visitor_errors_3d', fn() => $tail($R . '/.dar/errors.log', 3));
$s('visitor_errors_old', fn() => $tail($R . '/.dar/errors.1.log', 3));
$s('server_errors_3d', fn() => $tail(data_dir() . '/server-errors.log', 3));
$s('cfg', function () { $c = pl_cfg(); return ['on' => $c['on'], 'world' => $c['world'], 'photon' => $c['photon'], 'overpass' => array_map(fn($u) => parse_url($u, PHP_URL_HOST), $c['overpass']), 'geocoder' => parse_url($c['geocoder'], PHP_URL_HOST)]; });
$s('cache', function () { pl_table(); $r = []; foreach (db()->query("SELECT substr(k,1,instr(k||':',':')-1) AS kind, ok, COUNT(*) n, MAX(at) last FROM places_cache GROUP BY kind, ok")->fetchAll(PDO::FETCH_ASSOC) as $x) $r[] = [$x['kind'], (int)$x['ok'], (int)$x['n'], gmdate('c', (int)$x['last'])]; return $r; });
$s('cache_failed', function () { $r = []; foreach (db()->query("SELECT substr(k,1,instr(k||':',':')-1) AS kind, v, at FROM places_cache WHERE ok = 0 ORDER BY at DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC) as $x) $r[] = [$x['kind'], substr((string)$x['v'], 0, 160), gmdate('c', (int)$x['at'])]; return $r; });
$http = function (string $url, string $method = 'GET', ?string $body = null) { $ch = curl_init($url); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_USERAGENT => 'Dar/probe (read-only check; dar.ink)', CURLOPT_FOLLOWLOCATION => 1]); if ($body !== null) { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, 'data=' . rawurlencode($body)); } $t = microtime(true); $b = (string)curl_exec($ch); $e = curl_error($ch); $c = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); return ['code' => $c, 'ms' => (int)((microtime(true) - $t) * 1000), 'len' => strlen($b), 'head' => $e ?: substr(preg_replace('/\s+/', ' ', $b), 0, 90)]; };
$s('nominatim', fn() => $http('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . rawurlencode('Riyadh')));
$s('photon', fn() => $http('https://photon.komoot.io/api/?limit=1&q=' . rawurlencode('Riyadh')));
$s('overpass', function () use ($http) { $r = []; foreach (PL_OVERPASS as $u) $r[parse_url($u, PHP_URL_HOST)] = $http($u, 'POST', '[out:json][timeout:10];node(1);out;'); return $r; });
$s('tiles', fn() => $http('https://tile.openstreetmap.org/5/16/13.png'));
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
