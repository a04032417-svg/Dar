<?php
// Read only: the platform-wide settings the owner's notes are about, and the latest turns of his chats with his assistant.
// Every secret is masked (named secret fields, and any long token-like string); no subscriber's business data is read.
$W = '/home/u791571885/domains/dar.ink/public_html'; chdir($W); require $W . '/dar-sync.php';
$mask = function ($v) use (&$mask) {
    if (is_array($v)) { $o = []; foreach ($v as $k => $x) $o[$k] = (is_string($k) && preg_match('/secret|token|pass|pwd|api_?key|^key$|private|signing/i', $k) && is_scalar($x) && (string)$x !== '') ? '***' . substr((string)$x, -4) : $mask($x); return $o; }
    if (is_string($v)) return preg_replace_callback('/[A-Za-z0-9_\-]{28,}/', fn($m) => substr($m[0], 0, 4) . '…' . substr($m[0], -4), $v);
    return $v;
};
$cut = fn($s, $n) => mb_substr(is_string($s) ? $s : json_encode($s, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, $n);
$kv = fn($k) => ($r = one('SELECT v FROM kv WHERE k = ?', [$k])) ? json_decode((string)$r['v'], true) : null;
$meta = fn($k) => ($r = one('SELECT v FROM meta WHERE k = ?', [$k])) ? (json_decode((string)$r['v'], true) ?? (string)$r['v']) : null;
$out = [];
foreach (['plans', 'addons', 'countries', 'books', 'oauth_apps', 'gateways', 'faq'] as $k) $out['platform:' . $k] = $cut($mask($kv('db:platform:' . $k)), $k === 'plans' ? 9000 : 4000);
$out['kv:platform'] = $cut($mask($kv('platform')), 9000);
foreach (['rq_cfg', 'rq_manual', 'rq_custom', 'keys_report', 'keys_sweep', 'connect_cfg', 'channels', 'mail_cfg', 'ai_cfg', 'media_voice', 'wa_display', 'site_url', 'oauth_last:google', 'health', 'rq_checks'] as $k) $out['meta:' . $k] = $cut($mask($meta($k)), $k === 'rq_checks' ? 5000 : 2500);
// the owner's own chats with his assistant: the latest 40 turns, each cut short
try {
    $cols = array_column(q("PRAGMA table_info(mind_msg)")->fetchAll(), 'name'); $out['mind_msg_cols'] = $cols;
    $tcols = array_column(q("PRAGMA table_info(mind_thread)")->fetchAll(), 'name'); $out['mind_thread_cols'] = $tcols;
    $rows = q('SELECT * FROM mind_msg ORDER BY rowid DESC LIMIT 40')->fetchAll();
    $out['mind_msg_latest'] = array_map(fn($r) => array_map(fn($x) => is_string($x) ? $mask(mb_substr($x, 0, 420)) : $x, $r), array_reverse($rows));
} catch (Throwable $e) { $out['mind_err'] = $e->getMessage(); }
try { $out['eng_tasks'] = array_map(fn($r) => array_map(fn($x) => is_string($x) ? $mask(mb_substr($x, 0, 200)) : $x, $r), q('SELECT * FROM eng_tasks ORDER BY rowid DESC LIMIT 6')->fetchAll()); } catch (Throwable $e) { $out['eng_err'] = $e->getMessage(); }
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
