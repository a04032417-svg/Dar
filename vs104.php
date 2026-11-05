<?php
// AI Horde from the host, anonymously (its documented free key): which photographic models are up, whether an anonymous ask of a
// real-estate photograph is taken, how long it waits, and the picture it returns
ini_set('display_errors', '1'); error_reporting(E_ALL); @set_time_limit(900);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION]; $B = 'https://aihorde.net/api/v2'; $hd = ['Content-Type: application/json', 'apikey: 0000000000', 'Client-Agent: dar.ink:3.33:https://dar.ink'];
[$c, $r] = ai_http('GET', $B . '/status/models?type=image&min_count=1', $hd, null, 20); $m = json_decode((string)$r, true) ?: [];
usort($m, fn($a, $b) => ($b['count'] ?? 0) <=> ($a['count'] ?? 0)); $o['models'] = [$c, array_map(fn($x) => ($x['name'] ?? '') . ' w' . ($x['count'] ?? 0) . ' q' . ($x['queued'] ?? 0) . ' eta' . ($x['eta'] ?? ''), array_slice($m, 0, 25))];
$want = ['Juggernaut XL', 'AlbedoBase XL (SDXL)', 'ICBINP XL', 'RealVisXL', 'Dreamshaper XL', 'SDXL 1.0', 'Realistic Vision'];
$names = array_column($m, 'name'); $use = array_values(array_filter($want, fn($w) => in_array($w, $names, true)));
$o['use'] = $use;
$jobs = [];
foreach ([[1024, 576, 'wide'], [576, 1024, 'tall']] as $i => [$w, $h, $sz]) {
    $body = ['prompt' => 'Professional real-estate photograph, a contemporary villa facade with a landscaped front garden, contemporary Najdi architecture of Riyadh, warm sandstone walls, date palms, at golden hour, natural light, sharp focus, high detail, wide-angle architectural photography, photorealistic ### text, watermark, people, blurry, cartoon, painting, deformed', 'params' => ['width' => $w, 'height' => $h, 'steps' => 28, 'cfg_scale' => 6, 'sampler_name' => 'k_dpmpp_2m', 'karras' => true, 'n' => 1], 'models' => $use ?: ['AlbedoBase XL (SDXL)'], 'r2' => true, 'nsfw' => false, 'censor_nsfw' => true, 'shared' => false, 'slow_workers' => true];
    $t = microtime(true); [$c, $r] = ai_http('POST', $B . '/generate/async', $hd, json_encode($body), 30); $j = json_decode((string)$r, true) ?: [];
    $o['submit'][] = [$sz, $c, $j['id'] ?? '', $j['kudos'] ?? null, mb_substr((string)($j['message'] ?? ''), 0, 200), round(microtime(true) - $t, 1)]; if (!empty($j['id'])) $jobs[$sz] = $j['id'];
}
$t0 = time();
while ($jobs && time() - $t0 < 600) {
    sleep(15);
    foreach ($jobs as $sz => $id) {
        [$c, $r] = ai_http('GET', $B . '/generate/check/' . $id, $hd, null, 20); $j = json_decode((string)$r, true) ?: [];
        $o['checks'][$sz] = ['t' => time() - $t0, 'done' => $j['done'] ?? null, 'wait' => $j['wait_time'] ?? null, 'pos' => $j['queue_position'] ?? null, 'possible' => $j['is_possible'] ?? null, 'faulted' => $j['faulted'] ?? null];
        if (!empty($j['done']) || !empty($j['faulted']) || ($j['is_possible'] ?? true) === false) {
            [$c, $r] = ai_http('GET', $B . '/generate/status/' . $id, $hd, null, 30); $s = json_decode((string)$r, true) ?: []; $g = $s['generations'][0] ?? [];
            $img = (string)($g['img'] ?? ''); $bytes = ''; if ($img !== '') { [$c2, $bytes] = ai_http('GET', $img, [], null, 60); }
            $o['done'][$sz] = ['t' => time() - $t0, 'model' => $g['model'] ?? '', 'worker' => $g['worker_name'] ?? '', 'censored' => $g['censored'] ?? null, 'bytes' => strlen((string)$bytes), 'kind' => $bytes !== '' ? media_kind((string)$bytes) : ''];
            if ($bytes !== '' && function_exists('imagecreatefromstring') && ($im = @imagecreatefromstring((string)$bytes))) { $o['done'][$sz]['wh'] = imagesx($im) . 'x' . imagesy($im); }
            unset($jobs[$sz]);
        }
    }
}
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
