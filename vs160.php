<?php
// A read-only look: his «الرسائل المحفوظة» since 06:00 (his orders and the desk's answers), the WhatsApp assistant's latest turns. Nothing changed.
ini_set('display_errors', '1'); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$cut = fn($s, $n) => mb_substr(preg_replace('/\s+/u', ' ', (string)$s), 0, $n);
$o = ['desk' => array_map(fn($y) => gmdate('H:i:s', intdiv((int)$y['at'], 1000)) . (!empty($y['api']) ? ' DESK ' : ' HE ') . $cut($y['t'], 400), array_values(array_filter(tgu_log(tgu_self_id()), fn($y) => (int)$y['at'] > (time() - 5400) * 1000)))];
$th = one("SELECT id FROM mind_thread WHERE app = 'developer' ORDER BY rowid DESC LIMIT 1");
$o['whatsapp'] = array_map(fn($m) => gmdate('H:i', intdiv((int)$m['at'], 1000)) . ' ' . $m['role'] . ': ' . $cut($m['text'], 350), array_reverse(q('SELECT role, text, at FROM mind_msg WHERE thread = ? AND at > ? ORDER BY id DESC LIMIT 8', [$th['id'], (time() - 5400) * 1000])->fetchAll()));
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n";
