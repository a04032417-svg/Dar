<?php
// Turns OFF the automatic participation on the owner's own Telegram account (every chat «off», the persona off). Read-only otherwise.
ini_set('display_errors', '0'); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
meta_set('tgu_persona', json_encode(['on' => false]));
$g = tgu_chats(); foreach ($g as $id => $x) $g[$id]['mode'] = 'off'; tgu_chats_put($g);
echo json_encode(['persona' => tgu_persona()['on'], 'chats' => array_map(fn($x) => $x['mode'], tgu_chats())]), "\n";
