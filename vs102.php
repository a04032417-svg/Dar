<?php
// Pollinations from the host: back-to-back asks, keyed and open, then asks spaced out — what its door allows, and how long a photograph takes
ini_set('display_errors', '1'); error_reporting(E_ALL); @set_time_limit(900);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION]; $keys = array_values(array_map(fn($n) => (string)(vault_get($n) ?? ''), ai_keys('pollinations')));
$ask = function (string $key, string $p, string $size, int $to) { [$w, $h] = MEDIA_SIZES[$size]; $q = http_build_query(['model' => 'flux', 'width' => $w, 'height' => $h, 'seed' => random_int(1, 999999), 'nologo' => 'true', 'safe' => 'true']);
    $url = $key !== '' ? 'https://gen.pollinations.ai/image/' . rawurlencode($p) . '?' . $q : 'https://image.pollinations.ai/prompt/' . rawurlencode($p) . '?' . $q; $t = microtime(true);
    [$code, $res, $err] = ai_http('GET', $url, $key !== '' ? ['Authorization: Bearer ' . $key] : [], null, $to);
    return [$code, media_kind($res), strlen($res), round(microtime(true) - $t, 1), $code === 200 ? '' : mb_substr(preg_replace('/\s+/', ' ', strip_tags($res)), 0, 160), $err]; };
$P = ['modern apartment building exterior in Riyadh, warm sandstone, palm trees, golden hour', 'luxury villa living room with large windows, beige stone, soft daylight', 'empty residential land plot with road, desert city edge, clear sky', 'retail shop front on a commercial street, glass facade, evening'];
foreach ($keys as $ki => $k) foreach (array_slice($P, 0, 2) as $i => $p) $o['keyed_burst'][] = [$ki, $i] + $ask($k, 'Professional real-estate photograph, ' . $p . ', architectural photography, no text', 'wide', 50);
foreach (array_slice($P, 0, 2) as $i => $p) $o['open_burst'][] = $ask('', 'Professional real-estate photograph, ' . $p, 'wide', 50);
// spaced: one key, 8 s between asks
foreach ($P as $i => $p) { if ($i) sleep(8); $o['keyed_spaced'][] = $ask($keys[0] ?? '', 'Professional real-estate photograph, ' . $p . ', sharp, natural light', $i % 2 ? 'square' : 'tall', 50); }
// the other models the keyed door offers
[$code, $res] = ai_http('GET', 'https://gen.pollinations.ai/image/models', $keys ? ['Authorization: Bearer ' . $keys[0]] : [], null, 15); $o['models'] = [$code, mb_substr($res, 0, 900)];
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
