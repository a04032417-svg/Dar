<?php
// Dar — read-only look at the live site (2026-10-28, third): the services — which are on, what each waits for, the open incidents, the AI keys' state,
// the WhatsApp numbers' answers, the engineer's tasks, the radar and the offices around a map point. Writes nothing (the network reads are plain GETs/POSTs of public data).
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
$s = function (callable $f) { try { return $f(); } catch (Throwable $e) { return 'ERR ' . substr($e->getMessage(), 0, 200); } };
$o['feat_not_on'] = $s(fn() => array_filter(feat_states_all(), fn($v) => $v !== 'on'));
$o['feat_rq_why'] = $s(fn() => feat_rq_why());
$o['rq'] = $s(function () { $ctx = rq_ctx(); $x = []; foreach (rq_defs() as $d) { $it = rq_item($d, $ctx); $x[] = $d['id'] . ':' . $it['status']; } return $x; });
$o['incidents'] = $s(fn() => array_map(fn($r) => [$r['code'], $r['level'], $r['status'], mb_substr((string)$r['subject'], 0, 60), mb_substr((string)$r['info'], 0, 200), (int)$r['seen'], gmdate('m-d H:i', (int)$r['last_at'])], q("SELECT code, level, status, subject, info, seen, last_at FROM doc_inc WHERE status NOT IN ('fixed', 'gone') ORDER BY last_at DESC LIMIT 30")->fetchAll()));
$o['ai_keys'] = $s(function () { $c = ai_cfg(); $x = []; foreach ((array)($c['keys'] ?? []) as $k => $v) $x[] = [$k, (int)($v['rest_until'] ?? 0) > time() ? 'resting ' . (int)(((int)$v['rest_until'] - time()) / 60) . 'm' : 'ready', (string)($v['err'] ?? ''), mb_substr((string)($v['note'] ?? ''), 0, 120)]; return ['order' => $c['order'] ?? null, 'keys' => $x]; });
$o['ai_probe'] = $s(fn() => meta_get('ai_probe'));
$o['wa_in'] = $s(fn() => q("SELECT status, COUNT(*) n FROM wa_in WHERE at > ? GROUP BY status", [now_ms() - 7 * 86400000])->fetchAll());
$o['wa_in_last'] = $s(fn() => array_map(fn($r) => [gmdate('m-d H:i', intdiv((int)$r['at'], 1000)), substr((string)$r['frm'], 0, 5) . '…' . substr((string)$r['frm'], -3), $r['status'], mb_substr((string)$r['body'], 0, 40), mb_substr((string)$r['reply'], 0, 60)], q('SELECT at, frm, status, body, reply FROM wa_in ORDER BY at DESC LIMIT 10')->fetchAll()));
$o['wag'] = $s(fn() => function_exists('wag_cfg') ? array_intersect_key((array)wag_cfg(), array_flip(['on', 'url', 'state', 'mode'])) : 'no fn');
$o['wag_row'] = $s(fn() => function_exists('wag_row') ? array_intersect_key((array)wag_row('platform'), array_flip(['status', 'phone', 'upd'])) : 'no fn');
$o['eng'] = $s(fn() => array_map(fn($r) => [$r['id'], $r['status'], $r['phase'], mb_substr((string)$r['title'], 0, 50), mb_substr((string)$r['report'], 0, 160), gmdate('m-d H:i', intdiv((int)$r['at'], 1000))], q('SELECT id, status, phase, title, report, at FROM eng_tasks ORDER BY at DESC LIMIT 12')->fetchAll()));
$o['radar_kv'] = $s(fn() => q("SELECT ns, k, sz FROM kv WHERE k LIKE 'db:%:radar_%' ORDER BY sz DESC LIMIT 12")->fetchAll());
$o['radar_sources'] = $s(function () { $x = []; foreach (flow_rows('platform', 'radar_sources') as $r) $x[] = [($r['key'] ?? ''), ($r['kind'] ?? ''), ($r['method'] ?? ''), ($r['status'] ?? ''), mb_substr((string)($r['url'] ?? ''), 0, 60)]; return $x; });
$o['ai_radar'] = $s(function () { $r = radar_state(); return ['at' => $r['at'], 'models' => count($r['models']), 'sources' => array_map(fn($x) => is_array($x) ? array_intersect_key($x, array_flip(['id', 'ok', 'n', 'why'])) : $x, $r['sources'])]; });
$o['offices_probe'] = $s(function () {
    $x = []; foreach ([['Dammam', 26.4207, 50.0888, 15000], ['Dammam40', 26.4207, 50.0888, 40000], ['Qatif', 26.5651, 50.0089, 15000], ['Riyadh', 24.7136, 46.6753, 15000]] as [$n, $la, $lo, $r]) {
        [$ok, $body] = pl_overpass(pl_query('offices', $la, $lo, $r)); $x[$n] = $ok ? max(0, substr_count((string)$body, "\n") - 1) : 'ERR ' . $body; }
    return $x; });
$o['tail_errors'] = $s(function () { $f = data_dir() . '/server-errors.log'; return is_file($f) ? array_slice(log_tail($f, 6 * 3600)['groups'], 0, 8) : 'no file ' . $f; });
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
