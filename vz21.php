<?php
// Dar — read-only look at the live site (2026-10-29): why the website editor says «needs an AI provider» although the owner linked one, whether the
// yearly plans are off, the WhatsApp gateway's state, and the latest map/radar errors. No key, secret or number is printed. Nothing is changed.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
$s = function (string $label, callable $f) use (&$o) { try { $o[$label] = $f(); } catch (Throwable $e) { $o[$label] = 'ERR ' . substr($e->getMessage(), 0, 160); } };
$s('ai', function () {
    $cfg = ai_cfg(); $r = ['ready' => ai_ready($cfg), 'lang_ai_ok' => lang_ai_ok(), 'relay_ok' => function_exists('relay_ok') ? relay_ok() : null, 'open' => $cfg['open'] ?? null, 'providers' => []];
    foreach ($cfg['order'] as $id) { $sl = ai_slots($cfg, $id); $rest = []; foreach ($sl as $k) { $st = ai_key_state($cfg, $k); $rest[] = max(0, (int)($st['rest_until'] ?? 0) - time()); }
        $r['providers'][$id] = ['on' => $cfg['providers'][$id]['enabled'], 'base_ok' => ai_base_ok(ai_base($cfg, $id)), 'slots' => count($sl), 'open' => $sl ? ai_is_open($sl[0]) : null, 'rest_s' => $rest]; }
    return $r; });
$s('billing', fn() => ['yearly' => bill_cfg()['yearly'] ?? null, 'min_payout' => platform_rec()->min_payout ?? null]);
$s('wa', function () { $g = wag_cfg(); $r = ['ready' => wag_ready($g), 'host' => parse_url((string)$g['url'], PHP_URL_HOST), 'health' => wag_health(), 'platform_open' => wag_platform_open()]; if (function_exists('wag_contacts')) { $r['contacts_stc'] = wag_contacts('stc', '', 3); $r['contacts_stc']['items'] = count($r['contacts_stc']['items']); } else $r['contacts'] = 'not in this version'; $x = wag_call('GET', '/v1/sessions/platform/contacts?q=stc&limit=1', null, 8); $r['gw_contacts_route'] = $x['code']; return $r; });
$s('errors', function () { $f = data_dir() . '/server-errors.log'; if (!is_file($f)) return 'no log'; $sz = filesize($f); $fh = fopen($f, 'rb'); fseek($fh, max(0, $sz - 60000)); $t = (string)stream_get_contents($fh); $l = array_values(array_filter(explode("\n", $t), fn($x) => preg_match('/places|geocode|radar|map|osm|nominatim|overpass|web\.|talk|wa\b|wag/i', $x))); return array_map(fn($x) => substr($x, 0, 260), array_slice($l, -14)); });
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
