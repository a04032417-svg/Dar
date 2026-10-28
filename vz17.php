<?php
// Dar — read-only look at the live site (2026-10-28, third): the self-check's incidents that mailed the owner, the refused operations by interface, the AI keys' rest state,
// what Whop says to the company/card reading now, and the largest accounts. Changes nothing.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
$s = function (callable $f) { try { return $f(); } catch (Throwable $e) { return 'ERR ' . substr($e->getMessage(), 0, 200); } };
$o['doc_inc'] = $s(function () { if (!function_exists('doc_db')) return 'no doctor'; doc_db(); $x = [];
    foreach (q("SELECT id, code, level, status, told, person, remedy, info, ai, first_at, last_at, seen, subject, layer, chk FROM doc_inc ORDER BY id DESC LIMIT 14")->fetchAll() as $r) { $ai = o2a(jdec((string)$r['ai'])) ?: [];
        $x[] = [$r['id'], $r['code'], $r['level'], $r['status'], (int)$r['told'], $r['person'], substr((string)$r['info'], 0, 160), substr((string)($ai['summary'] ?? ''), 0, 200), gmdate('m-d H:i', (int)$r['first_at']), gmdate('m-d H:i', (int)$r['last_at']), (int)$r['seen'], substr((string)$r['subject'], 0, 60), $r['layer'], $r['chk']]; }
    return $x; });
$o['doc_cfg'] = $s(fn() => function_exists('doc_cfg') ? doc_cfg() : 'none');
$o['mail_3d'] = $s(function () { $x = []; foreach (q('SELECT kind, ok, COUNT(*) c, MIN(at) f, MAX(at) l FROM mail_log WHERE at > ? GROUP BY kind, ok ORDER BY l DESC LIMIT 20', [time() - 3 * 86400])->fetchAll() as $r) $x[] = [$r['kind'], (int)$r['ok'], (int)$r['c'], gmdate('m-d H:i', (int)$r['f']), gmdate('m-d H:i', (int)$r['l'])]; return $x; });
$o['refused_by_app'] = $s(function () { $x = []; foreach (q('SELECT app, op, code, SUM(n) n FROM op_refused WHERE day >= ? GROUP BY app, op, code ORDER BY n DESC LIMIT 20', [gmdate('Y-m-d', time() - 2 * 86400)])->fetchAll() as $r) $x[] = [$r['app'], $r['op'], $r['code'], (int)$r['n']]; return $x; });
$o['ai_since_install'] = $s(function () { $x = []; foreach (q("SELECT provider, ok, SUBSTR(code, 1, 40) code, COUNT(*) c FROM ai_log WHERE at > ? AND code NOT LIKE 'test%' GROUP BY provider, ok, SUBSTR(code, 1, 40) ORDER BY provider, ok DESC, c DESC", [(strtotime('2026-10-28T11:00:00Z')) * 1000])->fetchAll() as $r) $x[] = [$r['provider'], (int)$r['ok'], $r['code'], (int)$r['c']]; return $x; });
$o['ai_keys'] = $s(function () { $cfg = ai_cfg(); $x = []; foreach ($cfg['order'] as $id) { $sl = ai_slots($cfg, $id); $st = []; foreach ($sl as $k) { $a = ai_key_state($cfg, $k); $st[] = [substr($k, -3), max(0, (int)($a['rest_until'] ?? 0) - time()), (string)($a['err'] ?? '')]; } $x[$id] = ['on' => (bool)$cfg['providers'][$id]['enabled'], 'keys' => $st]; } return $x; });
$o['whop_now'] = $s(function () { $w = tr_whop(); $r0 = whop_company($w, false); $co = (string)($r0['id'] ?? ''); $m = fn($v) => strlen($v) > 8 ? substr($v, 0, 4) . '…' . substr($v, -3) : $v;
    $out = ['has_key' => $w['key'] !== '', 'own_key' => is_string(vault_get('p:tr.whop_key')), 'cfg_company' => $m((string)$w['company']), 'resolved_company' => $m($co), 'me_code' => $r0['r']['code'] ?? null, 'me_why' => isset($r0['r']) ? substr(whop_why($r0['r']), 0, 160) : null];
    $use = $co !== '' ? $co : (string)$w['company']; $r = tr_whop_api('GET', '/cards?first=1&account_id=' . rawurlencode($use)); $out['cards_code'] = $r['code']; $out['cards_why'] = whop_2xx($r) ? 'ok ' . count(whop_list($r)) : substr(whop_why($r), 0, 200);
    $out['card_saved'] = tr_card()['id'] ?? ''; $out['card_state'] = meta_get('tr_card_state'); return $out; });
$o['kv_top'] = $s(function () { $x = []; foreach (q('SELECT ns, COUNT(*) n, SUM(sz) b FROM kv GROUP BY ns ORDER BY b DESC LIMIT 8')->fetchAll() as $r) $x[] = [substr($r['ns'], 0, 12), (int)$r['n'], (int)$r['b']]; return $x; });
$o['health_flags'] = $s(fn() => jdec(meta_get('health')));
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
