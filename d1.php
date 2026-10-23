<?php
// read-only diagnosis of the office's WhatsApp link and its chats (prints facts; changes nothing)
require '/home/u791571885/domains/dar.ink/public_html/dar-sync.php';
$out = [];
$since = now_ms() - 4 * 3600000;
$out['sessions'] = array_map(fn($r) => ['sid' => $r['sid'], 'ns' => $r['ns'], 'status' => $r['status'], 'phone' => substr((string)$r['phone'], -4), 'at' => iso((int)$r['at']), 'data' => mb_substr((string)$r['data'], 0, 200)], q('SELECT * FROM wag_sessions')->fetchAll());
$out['events'] = q('SELECT sid, type, COUNT(*) n, MIN(at) a0, MAX(at) a1 FROM wag_events WHERE at > ? GROUP BY sid, type', [$since])->fetchAll();
foreach ($out['events'] as &$e) { $e['a0'] = iso((int)$e['a0']); $e['a1'] = iso((int)$e['a1']); } unset($e);
foreach ($out['sessions'] as $s) if ($s['ns'] !== 'platform') {
    $ns = $s['ns']; $cv = jdec(kv_get($ns, 'db:' . $ns . ':conversations')); $ms = jdec(kv_get($ns, 'db:' . $ns . ':messages'));
    $out['office'][$ns] = ['convs' => is_array($cv) ? count($cv) : null, 'msgs' => is_array($ms) ? count($ms) : null, 'link' => function_exists('conn_link') ? array_intersect_key(conn_link($ns, 'whatsapp') ?: [], array_flip(['status', 'mode', 'phone', 'at'])) : null];
    $g = wag_call('GET', '/v1/sessions/' . rawurlencode($s['sid'])); $out['gw'][$s['sid']] = ['code' => $g['code'], 'body' => mb_substr(jenc($g['json'] ?? $g['raw'] ?? ''), 0, 500)];
}
$lg = @file(data_dir() . '/server-errors.log') ?: []; $out['last_err'] = array_map(fn($l) => mb_substr(trim($l), 0, 300), array_slice($lg, -8));
echo jenc($out), "\n";
