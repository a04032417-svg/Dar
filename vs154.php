<?php
// A read-only look after 3.33.97: the new pieces are live, a chat's history carries #ids, the server's error groups (24 h). Nothing changed.
ini_set('display_errors', '1'); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'fns' => [function_exists('talk_tg_asked'), function_exists('talk_off_voice')], 'asked' => talk_tg_asked('رد على الرسالة الأخيرة في مجموعة تيليجرام الاولى'), 'voice' => talk_off_voice('أنا فرح، مساعد المطور في دار')];
$h = tgu_history('-1004293981841', 3); $o['hist'] = array_map(fn($x) => mb_substr($x, 0, 40), $h['messages'] ?? $h['kept'] ?? []);
$o['errors'] = array_map(fn($g) => [$g['where'], (int)$g['n'], $g['last'], mb_substr((string)$g['msg'], 0, 120)], array_slice(log_tail(data_dir() . '/server-errors.log', 86400)['groups'], 0, 6));
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n";
