<?php
// Dar — read-only look at the live site (2026-10-28): what the owner's screenshots point at. Writes nothing.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['v' => defined('SYNC_VERSION') ? SYNC_VERSION : '?', 'now' => gmdate('c')];
$s = function (callable $f) { try { return $f(); } catch (Throwable $e) { return 'ERR ' . substr($e->getMessage(), 0, 200); } };
$o['browserless'] = $s(fn() => rq_cache('browserless'));
$o['alerts_last'] = $s(fn() => rq_cache('alerts'));
$o['alert_state'] = $s(function () { $st = o2a(jdec(meta_get('rq_alert_state'))) ?: []; arsort($st); return array_slice(array_map(fn($t) => gmdate('m-d H:i', $t), $st), 0, 15, true); });
$o['server_errors_24h'] = $s(fn() => log_tail(data_dir() . '/server-errors.log', 86400)['groups']);
$o['visitor_errors_24h'] = $s(fn() => array_slice(log_tail($R . '/.dar/errors.log', 86400, 'error')['groups'], 0, 25));
$o['doctor_open'] = $s(fn() => doc_talk_view()['open']);
$o['ai_resting'] = $s(fn() => ai_resting(ai_cfg()));
$o['ai_recent'] = $s(fn() => q("SELECT provider, ok, code, COUNT(*) n, MAX(at) last FROM ai_log WHERE at > ? GROUP BY provider, ok, code ORDER BY n DESC LIMIT 25", [now_ms() - 86400000])->fetchAll());
$o['wa_in'] = $s(fn() => array_map(fn($r) => [substr((string)$r['frm'], -4), $r['status'], gmdate('m-d H:i', intdiv((int)$r['at'], 1000)), mb_substr((string)$r['body'], 0, 40), mb_substr((string)$r['reply'], 0, 60)], q('SELECT frm, status, at, body, reply FROM wa_in ORDER BY at DESC LIMIT 20')->fetchAll()));
$o['eng'] = $s(fn() => array_map(fn($t) => [$t['id'], $t['status'], mb_substr((string)$t['title'], 0, 60)], eng_brief(10)));
$o['tr_alerts'] = $s(fn() => q('SELECT kind, ref, text, at FROM tr_alerts ORDER BY id DESC LIMIT 8')->fetchAll());
$o['places_cache'] = $s(fn() => array_map(fn($r) => [$r['k'], $r['ok'], strlen((string)$r['v']), gmdate('m-d H:i', (int)$r['at'])], q("SELECT k, ok, v, at FROM places_cache ORDER BY at DESC LIMIT 12")->fetchAll()));
$o['feat_not_on'] = $s(function () { $a = feat_states_all(); return array_keys(array_filter($a, fn($x) => $x !== 'on')); });
$o['partner'] = $s(fn() => (array)(platform_rec()->partner ?? []));
$o['plans'] = $s(fn() => array_map(fn($p) => (array)$p, array_slice(array_values((array)(platform_rec()->plans ?? [])), 0, 12)));
$o['wa_gateway'] = $s(fn() => function_exists('wag_status') ? wag_status() : 'no fn');
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
