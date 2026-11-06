<?php
// Puts back what vs149 changed by mistake: the persona of the owner's Telegram account on again (the chats' modes are left as they are now).
ini_set('display_errors', '1'); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
meta_set('tgu_persona', json_encode(['on' => true]));
echo json_encode(['persona' => tgu_persona(), 'chats' => array_map(fn($x) => [$x['title'] ?? '', $x['mode'] ?? ''], tgu_chats())], JSON_UNESCAPED_UNICODE), "\n";
