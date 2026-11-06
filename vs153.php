<?php
// A read-only look: the assistant's latest turns (with what it ran), the first Telegram group's latest messages with their ids. Nothing changed.
ini_set('display_errors', '1'); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$cut = fn($s, $n) => mb_substr(preg_replace('/\s+/u', ' ', (string)$s), 0, $n); $o = [];
$th = one("SELECT id FROM mind_thread WHERE app = 'developer' ORDER BY rowid DESC LIMIT 1");
$o['turns'] = array_map(fn($m) => gmdate('H:i', intdiv((int)$m['at'], 1000)) . ' ' . $m['role'] . ': ' . $cut($m['text'], 500) . ' META ' . $cut($m['meta'], 700), array_reverse(q('SELECT role, text, meta, at FROM mind_msg WHERE thread = ? ORDER BY id DESC LIMIT 4', [$th['id']])->fetchAll()));
$o['log'] = array_map(fn($y) => gmdate('H:i', intdiv((int)$y['at'], 1000)) . ' #' . ($y['mid'] ?? '') . ($y['out'] ? ' OUT' : ' IN') . ' ' . ($y['from'] ?? '') . ': ' . $cut($y['t'], 160), array_slice(tgu_log('-1004293981841'), -8));
$o['hist'] = tgu_history('-1004293981841', 8);
$o['chats'] = array_map(fn($x) => [$x['title'] ?? '', $x['mode'] ?? '', date('m-d H:i', (int)($x['since'] ?? 0))], tgu_chats());
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n";
