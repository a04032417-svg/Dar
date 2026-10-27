<?php
// Dar — a read-only look after 3.33.26 (2026-10-28): what the owner's screenshots point at, as the live server holds it
$R = '/home/u791571885/domains/dar.ink/public_html'; require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
$s = function (callable $f) { try { return $f(); } catch (Throwable $e) { return 'ERR ' . substr($e->getMessage(), 0, 200); } };
$o['browserless'] = $s(fn() => rq_cache('browserless'));
$o['alerts_last'] = $s(fn() => rq_cache('alerts'));
$o['alert_state'] = $s(function () { $st = o2a(jdec(meta_get('rq_alert_state'))) ?: []; arsort($st); return array_slice(array_map(fn($t) => gmdate('m-d H:i', $t), $st), 0, 15, true); });
$o['errors'] = $s(function () { $f = data_dir() . '/server-errors.log'; $l = is_file($f) ? array_slice(file($f), -400) : []; $c = []; foreach ($l as $x) { $j = json_decode($x, true); if (!$j) continue; $k = substr(($j['where'] ?? '') . ' | ' . ($j['msg'] ?? ''), 0, 160); $c[$k] = [($c[$k][0] ?? 0) + 1, $j['t'] ?? '']; } uasort($c, fn($a, $b) => $b[0] <=> $a[0]); return array_slice($c, 0, 25, true); });
$o['client_errors'] = $s(function () { $f = data_dir() . '/client-errors.log'; if (!is_file($f)) { $f = __DIR__; return 'no file'; } $l = array_slice(file($f), -300); $c = []; foreach ($l as $x) { $j = json_decode($x, true); if (!$j) continue; $k = substr(($j['app'] ?? '') . ' ' . ($j['route'] ?? '') . ' | ' . ($j['where'] ?? '') . ' | ' . ($j['msg'] ?? ''), 0, 200); $c[$k] = [($c[$k][0] ?? 0) + 1, $j['t'] ?? '', $j['v'] ?? '']; } uasort($c, fn($a, $b) => $b[0] <=> $a[0]); return array_slice($c, 0, 25, true); });
$o['doctor_open'] = $s(fn() => array_map(fn($x) => [$x['level'] ?? '', $x['what'] ?? '', $x['subject'] ?? '', mb_substr((string)($x['detail'] ?? ($x['why'] ?? '')), 0, 160)], array_slice(doc_talk_view()['open'] ?? [], 0, 12)));
$o['ai'] = $s(function () { $cfg = ai_cfg(); return ['ready' => ai_ready($cfg), 'resting' => ai_resting($cfg), 'order' => $cfg['order'] ?? null]; });
$o['ai_recent'] = $s(fn() => all("SELECT provider, ok, code, COUNT(*) n, MAX(at) last FROM ai_log WHERE at > ? GROUP BY provider, ok, code ORDER BY n DESC LIMIT 25", [now_ms() - 86400000]));
$o['wa_in'] = $s(fn() => array_map(fn($r) => [substr((string)$r['frm'], -4), $r['status'], gmdate('m-d H:i', intdiv((int)$r['at'], 1000)), mb_substr((string)$r['body'], 0, 40)], all('SELECT frm, status, at, body FROM wa_in ORDER BY at DESC LIMIT 25')));
$o['eng'] = $s(fn() => array_map(fn($t) => [$t['id'] ?? '', $t['status'] ?? '', mb_substr((string)($t['title'] ?? ''), 0, 60), mb_substr((string)($t['report'] ?? ''), 0, 220)], array_slice(eng_brief(10), 0, 10)));
$o['tr_alerts'] = $s(fn() => all('SELECT kind, ref, text, at FROM tr_alerts ORDER BY id DESC LIMIT 8'));
$o['places_fail'] = $s(fn() => array_map(fn($r) => [$r['k'], $r['ok'], gmdate('m-d H:i', (int)$r['at']), mb_substr((string)$r['v'], 0, 120)], all("SELECT k, ok, at, v FROM places_cache WHERE k LIKE 'osm:offices%' ORDER BY at DESC LIMIT 10")));
$o['plans'] = $s(fn() => array_map(fn($p) => [$p->code ?? '', $p->audience ?? '', $p->base_price ?? null, $p->active ?? null, $p->trial_days ?? null], array_values(array_filter((array)bill_platform_rows('plans'), 'is_object'))));
$o['feat_soon'] = $s(function () { $a = feat_states_all(); return array_keys(array_filter($a, fn($x) => $x !== 'on')); });
$o['renew'] = $s(fn() => function_exists('bill_cfg') ? bill_cfg(platform_rec())['renew'] : null);
$o['partner'] = $s(fn() => ['min_payout' => platform_rec()->partner->min_payout ?? null, 'offer' => platform_rec()->partner->offer ?? null]);
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
