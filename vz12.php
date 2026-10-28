<?php
// Dar — read-only look at the live site (2026-10-28, fifth): every WhatsApp link (each office's number: state, last events, what it sent), the offices on the platform,
// and which worldwide map sources answer from this host (one asking each, once). Writes nothing.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
$s = function (callable $f) { try { return $f(); } catch (Throwable $e) { return 'ERR ' . substr($e->getMessage(), 0, 200); } };
$mask = fn($p) => strlen((string)$p) > 6 ? substr((string)$p, 0, 5) . '…' . substr((string)$p, -3) : (string)$p;
$o['wag_cfg'] = $s(fn() => array_intersect_key((array)wag_cfg(), array_flip(['on', 'url', 'state', 'mode', 'ready'])));
$o['wag_sessions'] = $s(function () use ($mask) { wag_db(); $x = []; foreach (q('SELECT sid, ns, status, phone, mode, at, data FROM wag_sessions ORDER BY at DESC LIMIT 40')->fetchAll() as $r) { $d = o2a(jdec((string)$r['data'])) ?: []; $x[] = [substr($r['sid'], 0, 14), substr($r['ns'], 0, 12), $r['status'], $mask($r['phone']), $r['mode'], gmdate('m-d H:i', intdiv((int)$r['at'], 1000) > 1e9 ? intdiv((int)$r['at'], 1000) : (int)$r['at']), array_slice(array_keys($d), 0, 8), substr((string)($d['err'] ?? $d['error'] ?? $d['why'] ?? ''), 0, 80)]; } return $x; });
$o['wag_events'] = $s(function () { return array_map(fn($r) => [substr($r['sid'], 0, 14), $r['type'], gmdate('m-d H:i', intdiv((int)$r['at'], 1000) > 1e9 ? intdiv((int)$r['at'], 1000) : (int)$r['at'])], q('SELECT sid, type, at FROM wag_events ORDER BY at DESC LIMIT 25')->fetchAll()); });
$o['tenants'] = $s(function () { return array_map(fn($r) => [substr($r['id'], 0, 12), $r['status'] ?? '', $r['app'] ?? '', $r['country'] ?? '', $r['plan'] ?? ''], q('SELECT id, status, app, country, plan FROM tenants ORDER BY rowid DESC LIMIT 30')->fetchAll()); });
$o['outbox'] = $s(function () { $x = []; foreach (q('SELECT id FROM tenants LIMIT 40')->fetchAll() as $t) { $c = []; foreach (flow_rows((string)$t['id'], 'outbox') as $r) { $k = ($r['channel'] ?? '?') . ':' . ($r['status'] ?? '?'); $c[$k] = ($c[$k] ?? 0) + 1; } if ($c) $x[substr($t['id'], 0, 12)] = $c; } return $x; });
$o['wa_numbers'] = $s(function () { $x = []; foreach (q('SELECT id FROM tenants LIMIT 40')->fetchAll() as $t) { $n = count(flow_rows((string)$t['id'], 'wa_numbers')); if ($n) $x[substr($t['id'], 0, 12)] = $n; } return $x; });
// the worldwide sources, once each, in parallel per city
$cities = [['Dammam', 26.4207, 50.0888], ['Riyadh', 24.7136, 46.6753], ['London', 51.5074, -0.1278], ['NewYork', 40.7128, -74.0060], ['Jakarta', -6.2088, 106.8456], ['Cairo', 30.0444, 31.2357]];
$ua = 'Dar/probe (real-estate platform; dar.ink; one-time source check)';
$mk = function (string $url, ?string $post = null, int $tm = 25) use ($ua) { $ch = curl_init($url); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $tm, CURLOPT_CONNECTTIMEOUT => 6, CURLOPT_USERAGENT => $ua, CURLOPT_HTTPHEADER => ['Accept: application/json,text/csv,*/*']]); if ($post !== null) { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $post); } return $ch; };
$mirrors = ['ovde' => 'https://overpass-api.de/api/interpreter', 'kumi' => 'https://overpass.kumi.systems/api/interpreter', 'coffee' => 'https://overpass.private.coffee/api/interpreter', 'fr' => 'https://overpass.openstreetmap.fr/api/interpreter', 'jp' => 'https://overpass.osm.jp/api/interpreter', 'tw' => 'https://overpass.nchc.org.tw/api/interpreter', 'mailru' => 'https://maps.mail.ru/osm/tools/overpass/api/interpreter'];
$o['sources'] = $s(function () use ($cities, $mk, $mirrors) {
    $out = [];
    foreach ($cities as $ci => [$n, $la, $lo]) {
        $mh = curl_multi_init(); $hs = [];
        $d = 0.18; $vb = ($lo - $d) . ',' . ($la + $d) . ',' . ($lo + $d) . ',' . ($la - $d);
        $hs['photon'] = $mk('https://photon.komoot.io/api/?' . http_build_query(['q' => 'real estate', 'lat' => $la, 'lon' => $lo, 'osm_tag' => 'office:estate_agent', 'limit' => 40]));
        $hs['photon_n'] = $mk('https://photon.komoot.io/api/?' . http_build_query(['q' => 'عقار', 'lat' => $la, 'lon' => $lo, 'limit' => 40, 'lang' => 'default']));
        $hs['nomi'] = $mk('https://nominatim.openstreetmap.org/search?' . http_build_query(['q' => 'estate agent', 'viewbox' => $vb, 'bounded' => 1, 'format' => 'jsonv2', 'limit' => 40]));
        if ($ci < 3) { $ql = '[out:csv(::type,::id,::lat,::lon,name;true;"\t")][timeout:20];(nwr(around:15000,' . $la . ',' . $lo . ')["office"~"^(estate_agent|real_estate|property_management|real_estate_agent)$"];nwr(around:15000,' . $la . ',' . $lo . ')["shop"="estate_agent"];);out center qt 200;'; foreach ($mirrors as $k => $u) $hs['op_' . $k] = $mk($u, 'data=' . rawurlencode($ql), 24); }
        foreach ($hs as $ch) curl_multi_add_handle($mh, $ch);
        $t0 = microtime(true); $run = 0; do { curl_multi_exec($mh, $run); if ($run) curl_multi_select($mh, 0.5); } while ($run && microtime(true) - $t0 < 30);
        foreach ($hs as $k => $ch) {
            $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE); $b = (string)curl_multi_getcontent($ch); $ms = (int)round(curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000); $n2 = '';
            if ($code === 200) { if (str_starts_with($k, 'photon')) { $j = json_decode($b, true); $n2 = count($j['features'] ?? []); } elseif ($k === 'nomi') { $j = json_decode($b, true); $n2 = is_array($j) ? count($j) : 'bad'; } else $n2 = max(0, substr_count($b, "\n") - 1); }
            $out[$n][$k] = [$code, $ms, $code === 200 ? $n2 : substr((string)curl_error($ch), 0, 50)];
            curl_multi_remove_handle($mh, $ch); curl_close($ch);
        }
        curl_multi_close($mh);
    }
    return $out; });
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
