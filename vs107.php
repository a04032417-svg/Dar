<?php
// The bank and the visitors' sites a little later (photographs placed, stand-ins left), and what the minute's duty did last
ini_set('display_errors', '1'); error_reporting(E_ALL); @set_time_limit(200);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION, 'bank' => pbank_count(), 'by_rg' => q('SELECT rg, COUNT(*) n FROM pbank GROUP BY rg')->fetchAll(), 'horde_flying' => count(o2a(jdec((string)(meta_get('pbank_hd') ?? ''))) ?: []), 'horde_rest' => max(0, (int)(meta_get('pbank_hd_rest') ?? 0) - time())];
foreach (['mw31vvde63bc4d', 'mw33q5soc31956', 'mw34fvwcef20ff'] as $id) { $r = o2a(jdec((string)meta_get('gs:' . $id))); $all = json_encode($r['d'] ?? [], JSON_UNESCAPED_UNICODE); $o['sites'][$id] = ($r['st'] ?? '') . ' ' . substr_count($all, 'data-ai-img') . '/' . preg_match_all('/\{\{\s*img\b/', $all); }
$c = curl_init('https://dar.ink/free/site/mw34fvwcef20ff/'); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]); $h = (string)curl_exec($c); $o['served'] = [curl_getinfo($c, CURLINFO_HTTP_CODE), substr_count($h, 'data-ai-img'), substr_count($h, 'x-art')]; curl_close($c);
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
