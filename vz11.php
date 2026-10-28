<?php
// Dar — read-only look at the live site (2026-10-28, fourth): which services the developer set himself (not the platform), and how fast the map's public servers answer
// the two small office queries (tags, names) around three cities. Writes nothing.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
$s = function (callable $f) { try { return $f(); } catch (Throwable $e) { return 'ERR ' . substr($e->getMessage(), 0, 200); } };
$o['own_not_on'] = $s(function () { $x = []; foreach (array_keys(feat_defs()) as $id) { $st = feat_state($id, null, [], false); if ($st !== 'on') $x[$id] = $st; } return $x; });
$o['maps_cfg'] = $s(fn() => ['overpass' => pl_cfg()['overpass'], 'on' => pl_cfg()['on']]);
$rx = 'عقار|عقاري|عقارات|سمسار|real ?estate|realty|propert(y|ies)|estate ?agen|immobili|agence immo';
$head = '[out:csv(::type,::id,::lat,::lon,name;true;"\t")][timeout:25];(';
$q = function (string $kind, float $la, float $lo, int $r) use ($head, $rx): string {
    $a = '(around:' . $r . ',' . $la . ',' . $lo . ')'; $an = '(around:' . min($r, 10000) . ',' . $la . ',' . $lo . ')';
    $tags = 'nwr' . $a . '["office"~"^(estate_agent|real_estate|property_management|real_estate_agent)$"];nwr' . $a . '["shop"="estate_agent"];';
    $names = 'nwr' . $an . '["office"]["name"~"' . $rx . '",i];nwr' . $an . '["shop"]["name"~"' . $rx . '",i];nwr' . $an . '["office"]["name:ar"~"' . $rx . '"];';
    return $head . ($kind === 'tags' ? $tags : $names) . ');out center qt 500;';
};
$o['overpass'] = $s(function () use ($q) {
    $x = [];
    foreach ([['Dammam', 26.4207, 50.0888], ['Qatif', 26.5651, 50.0089], ['Riyadh', 24.7136, 46.6753]] as [$n, $la, $lo]) foreach (['tags' => 40000, 'names' => 10000] as $kind => $r) {
        foreach (PL_OVERPASS as $i => $url) {
            $t0 = microtime(true); [$code, $err, $body] = http_out('POST', $url, ['Content-Type: application/x-www-form-urlencoded', 'User-Agent: ' . pl_ua(), 'Accept: text/csv,text/plain,*/*'], 'data=' . rawurlencode($q($kind, $la, $lo, $r)), 28, 4000000);
            $x[$n . ':' . $kind . ':m' . $i] = [$code, (int)round((microtime(true) - $t0) * 1000), $code === 200 ? max(0, substr_count((string)$body, "\n") - 1) : substr((string)$err, 0, 60)];
            if ($code === 200) break;
        }
    }
    return $x; });
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
