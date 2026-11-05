<?php
// Why the studio's live site got one photograph: each image lane asked once, in turn, with its answer and time; what the served page shows in place of the rest
ini_set('display_errors', '1'); error_reporting(E_ALL); @set_time_limit(600);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION]; $cfg = ai_cfg();
$o['order'] = $cfg['order'] ?? null; $o['open'] = $cfg['open'] ?? null;
$lanes = media_image_lanes($cfg); $o['lanes'] = array_map(fn($l) => $l[0] . ':' . substr(md5($l[1]), 0, 6), $lanes);
foreach (['gemini', 'cloudflare', 'pollinations', 'openai'] as $id) $o['prov'][$id] = ['enabled' => !empty($cfg['providers'][$id]['enabled']), 'keys' => count(ai_keys($id)), 'states' => array_map(fn($n) => array_intersect_key(ai_key_state($cfg, $n), ['rest_until' => 1, 'err' => 1]), ai_keys($id))];
$o['gem_models'] = $cfg['models']['gemini']['image'] ?? null;
$p = 'Professional real-estate photograph, contemporary Najdi villa facade in north Riyadh at golden hour, natural light, sharp, architectural photography, no text';
foreach ($lanes as $i => [$id, $slot]) {
    if ($i > 5) break; $key = substr($slot, 0, 5) === 'open:' ? '' : (string)(vault_get($slot) ?? ''); $t = microtime(true);
    try {
        $r = $id === 'gemini' ? media_try_gemini($cfg, $key, $p, 'wide', 40) : ($id === 'openai' ? media_try_openai($cfg, $key, $p, 'wide', 40) : ($id === 'cloudflare' ? media_try_cloudflare($cfg, $key, $p, 'wide', 40) : media_try_pollinations($key, $p, 'wide', 40)));
        $o['try'][] = [$id, $r[0] !== null ? strlen($r[0]) : 0, $r[1], $r[2], round(microtime(true) - $t, 1)];
        // a second ask right after (does the lane give one a minute?)
        $t = microtime(true); $r = $id === 'gemini' ? media_try_gemini($cfg, $key, $p . ', wide street view', 'square', 40) : ($id === 'openai' ? [null, '', 'skip'] : ($id === 'cloudflare' ? media_try_cloudflare($cfg, $key, $p . ', wide street view', 'square', 40) : media_try_pollinations($key, $p . ', wide street view', 'square', 40)));
        $o['try2'][] = [$id, $r[0] !== null ? strlen($r[0]) : 0, $r[1], $r[2], round(microtime(true) - $t, 1)];
    } catch (Throwable $e) { $o['try'][] = [$id, 'ex', $e->getMessage()]; }
}
// the site made earlier: how many photographs, how many drawn stand-ins
$rec = o2a(jdec((string)meta_get('gs:mw31vvde63bc4d'))); $all = json_encode($rec['d'] ?? [], JSON_UNESCAPED_UNICODE);
$o['site'] = ['img' => substr_count($all, 'data-ai-img'), 'tokens_left' => preg_match_all('/\{\{\s*img\b/', $all), 'sample_src' => preg_match('/src=\\\\?"([^"\\\\]{0,120})/', $all, $m) ? $m[1] : ''];
$c = curl_init('https://dar.ink/free/site/mw31vvde63bc4d/'); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]); $h = (string)curl_exec($c); curl_close($c);
$o['served'] = ['len' => strlen($h), 'x_art' => substr_count($h, 'x-art'), 'img' => substr_count($h, '<img')];
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
