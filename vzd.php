<?php
// Dar — read-only diagnosis (nothing is changed or sent): why an office's smart employee stopped answering on WhatsApp, how long the
// owner's assistant and the engineer take, the AI providers' times, and the server's faults of the last hours
$R = '/home/u791571885/domains/dar.ink/public_html'; require $R . '/dar-sync.php';
$since = now_ms() - 8 * 3600000; $o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
$cut = fn($s, $n = 60) => mb_substr((string)$s, 0, $n);
if (function_exists('wag_db')) { wag_db(); $o['sessions'] = array_map(fn($r) => ['sid' => $r['sid'], 'ns' => $r['ns'], 'status' => $r['status'], 'mode' => $r['mode'], 'at' => gmdate('c', (int)($r['at'] / 1000))], q('SELECT * FROM wag_sessions')->fetchAll()); }
$o['offices'] = [];
foreach ($o['sessions'] ?? [] as $s) {
    $ns = (string)$s['ns']; if ($ns === 'platform') continue; [$T, $trow] = conn_tenant($ns); if (!$T) continue;
    $l = conn_link($ns, 'whatsapp'); $cfg = o2a(jdec(kv_get($ns, 'set:' . $ns . ':whatsapp_agent'))) ?: [];
    $x = ['ns' => $ns, 'name' => (string)($T->name ?? ''), 'status' => (string)($T->status ?? ''), 'demo' => !empty($T->demo), 'plan' => (string)($T->plan ?? ''), 'country' => (string)($T->country ?? ''),
        'link' => ['status' => $l['status'] ?? '', 'auto' => conn_auto('whatsapp', $l)], 'agent_on' => conn_agent_on($ns, $T, $trow), 'agent_plan' => conn_agent_plan(['tenant' => $ns, 'trow' => $trow]), 'ai_ready' => function_exists('flow_ai_ready') ? flow_ai_ready() : null,
        'wa_ai' => function_exists('feat_ok') ? feat_ok(['dev' => false, 'tenant' => $ns, 'trow' => $trow, 'user' => ['id' => 'smart_agent']], 'wa.ai') : null, 'feats' => ['inbox' => wag_feat($ns, 'wa.inbox'), 'assistant' => wag_feat($ns, 'wa.assistant')], 'agent_cfg_keys' => array_keys($cfg),
        'msgs_quota' => function_exists('quota_limits') ? ['limits' => quota_limits($ns)['msgs'] ?? null, 'fits' => quota_fits($ns, 'msgs'), 'month' => quota_used($ns, 'msgs', quota_per('m')), 'day' => quota_used($ns, 'msgs', quota_per('d'))] : null,
        'props' => ['all' => count(flow_rows($ns, 'properties')), 'shown_to_agent' => count(conn_facts($ns, $T)[0])]];
    $convs = array_values(array_filter(flow_rows($ns, 'conversations'), fn($c) => strtotime((string)($c['updated'] ?? '')) * 1000 > $since));
    foreach (array_slice($convs, 0, 4) as $c) {
        $cid = (string)$c['id']; $ext = (string)($c['ext'] ?? ''); $hit = function (string $b) { $r = one('SELECT n, at FROM hits WHERE k = ?', [hash('sha256', $b . '|*')]); return $r ? ['n' => (int)$r['n'], 'at' => gmdate('c', (int)$r['at'])] : null; };
        $msgs = array_values(array_filter(flow_rows($ns, 'messages'), fn($m) => (string)($m['conversation'] ?? '') === $cid && strtotime((string)($m['created'] ?? '')) * 1000 > $since)); usort($msgs, fn($a, $b) => strcmp((string)$a['created'], (string)$b['created']));
        $x['convs'][] = ['ext_tail' => substr($ext, -4), 'contact' => $cut($c['contact'] ?? '', 30), 'agent_off_until' => $c['agent_off_until'] ?? null, 'hits' => ['agent' => $hit('conn_agent:' . $ns . ':whatsapp:' . $ext), 'in' => $hit('conn_in:' . $ns . ':whatsapp:' . $ext)],
            'msgs' => array_map(fn($m) => [substr((string)$m['created'], 11, 8), $m['direction'] ?? '', $m['via'] ?? '', $m['status'] ?? '', $cut($m['error'] ?? '', 80), $cut($m['body'] ?? '', 50)], array_slice($msgs, -25))];
    }
    $x['agent_day'] = (function () use ($ns) { $r = one('SELECT n, at FROM hits WHERE k = ?', [hash('sha256', 'conn_agent_day:' . $ns . '|*')]); return $r ? (int)$r['n'] : 0; })();
    $o['offices'][] = $x;
}
// the owner's own WhatsApp: how long each message waited for its answer
if (function_exists('wa_in_db')) { wa_in_db(); $o['owner_wa'] = array_map(fn($r) => [gmdate('H:i:s', (int)($r['at'] / 1000)), $r['status'], $r['done_at'] ? round(((int)$r['done_at'] - (int)$r['at']) / 1000, 1) : null, $cut($r['body'] ?? '', 30)], q('SELECT * FROM wa_in WHERE at > ? ORDER BY at DESC LIMIT 25', [$since])->fetchAll()); }
// the engineer: when each task was asked, started and ended
if (function_exists('eng_db')) { eng_db(); $o['eng'] = array_map(function ($r) { $log = json_decode((string)($r['log'] ?? '[]'), true) ?: []; $st = null; foreach ($log as $e) if (($e['kind'] ?? '') === 'start') { $st = $e['at'] ?? null; break; } return ['id' => $r['id'], 'title' => mb_substr((string)$r['title'], 0, 40), 'status' => $r['status'], 'asked' => gmdate('H:i:s', (int)($r['at'] / 1000)), 'start' => $st, 'upd' => gmdate('H:i:s', (int)($r['upd'] / 1000)), 'calls' => (int)$r['calls'], 'ms' => (int)($r['ms'] ?? 0), 'kinds' => array_count_values(array_map(fn($e) => (string)($e['kind'] ?? ''), $log))]; }, q('SELECT * FROM eng_tasks WHERE at > ? ORDER BY at DESC LIMIT 10', [$since])->fetchAll()); }
// the AI providers in the last hours
$o['ai'] = q('SELECT provider, COUNT(*) n, SUM(ok) ok, CAST(AVG(ms) AS INTEGER) avg_ms, MAX(ms) max_ms FROM ai_log WHERE at > ? GROUP BY provider ORDER BY n DESC', [$since])->fetchAll();
$o['ai_fail_codes'] = q("SELECT provider, code, COUNT(*) n FROM ai_log WHERE at > ? AND ok = 0 GROUP BY provider, code ORDER BY n DESC LIMIT 15", [$since])->fetchAll();
$o['cron'] = ['cron_at' => meta_get('cron_at'), 'cron_cli_at' => meta_get('cron_cli_at'), 'wag_tick_at' => meta_get('wag_tick_at')];
// the server's faults of the last hours (grouped)
$lt = log_tail(data_dir() . '/server-errors.log', 8 * 3600); $o['faults'] = ['count' => $lt['n'], 'groups' => array_map(fn($g) => [$cut($g['msg'], 160), $cut($g['where'], 60), $g['n'], $g['last']], array_slice($lt['groups'], 0, 15))];
$vt = log_tail($R . '/.dar/errors.log', 8 * 3600, 'error'); $o['visitor_errors'] = ['count' => $vt['n'], 'groups' => array_map(fn($g) => [$cut($g['msg'], 160), $cut($g['where'], 60), $g['n'], $g['last']], array_slice($vt['groups'], 0, 15))];
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n";
