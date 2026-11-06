<?php
// A read-only look: the engineer task the owner saw fail, his latest words to the assistant, his Telegram chats. Nothing changed.
ini_set('display_errors', '1'); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$S = $argv[1] ?? 'eng'; $o = [];
$cut = fn($s, $n) => mb_substr(preg_replace('/\s+/u', ' ', (string)$s), 0, $n);
if ($S === 'eng') {
    $t = eng_row('etmw4gmhebd332') ?? eng_row((string)(one("SELECT id FROM eng_tasks ORDER BY upd DESC LIMIT 1")['id'] ?? ''));
    $o = ['id' => $t['id'], 'ask' => $cut($t['ask'], 900), 'status' => $t['status'], 'calls' => $t['calls'], 'via' => $t['via'], 'level' => $t['level'],
        'log' => array_map(fn($x) => ($x['kind'] ?? '') . '|' . var_export($x['ok'] ?? null, true) . '|' . $cut($x['text'] ?? '', 200) . (isset($x['err']) ? ' ERR ' . $cut($x['err'], 120) : ''), $t['log']),
        'convo' => array_map(fn($m) => $m['role'] . ': ' . $cut($m['content'], 420), array_slice($t['convo'], 0, 14))];
} elseif ($S === 'talk') {
    $th = q("SELECT id FROM mind_thread WHERE app = 'developer' ORDER BY rowid DESC LIMIT 3")->fetchAll();
    foreach ($th as $r) $o[$r['id']] = array_map(fn($m) => gmdate('H:i', intdiv((int)$m['at'], 1000) ?: 0) . ' ' . $m['role'] . ': ' . $cut($m['text'], 330), array_reverse(q('SELECT role, text, at FROM mind_msg WHERE thread = ? ORDER BY id DESC LIMIT 7', [$r['id']])->fetchAll()));
} else {
    $o['status'] = json_decode((string)meta_get('tgu_status'), true); $o['persona'] = tgu_persona();
    foreach (tgu_chats() as $id => $x) $o['chats'][$id] = [$cut($x['title'] ?? '', 40), $x['type'] ?? '', $x['mode'] ?? '', array_map(fn($y) => gmdate('H:i', intdiv((int)$y['at'], 1000)) . ($y['out'] ? ' OUT' : ' IN') . (!empty($y['api']) ? '(api)' : '') . ' ' . $cut($y['t'], 70), array_slice(tgu_log((string)$id), -4))];
    try { $o['faults'] = array_map(fn($r) => $cut(json_encode($r, JSON_UNESCAPED_UNICODE), 260), q("SELECT * FROM faults ORDER BY rowid DESC LIMIT 5")->fetchAll()); } catch (Throwable $e) { $o['faults'] = $e->getMessage(); }
}
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n";
