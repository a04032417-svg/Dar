<?php
// A read-only look: did his «الرسائل المحفوظة» messages reach Dar, and what happened to them. Nothing changed.
ini_set('display_errors', '1'); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$self = tgu_self_id(); $o = ['v' => SYNC_VERSION, 'self' => $self, 'me' => tgu_me(), 'status' => json_decode((string)meta_get('tgu_status'), true)];
$o['self_log'] = array_map(fn($y) => gmdate('H:i:s', intdiv((int)$y['at'], 1000)) . ' #' . ($y['mid'] ?? '') . (!empty($y['out']) ? ' OUT' : ' IN') . (!empty($y['api']) ? '(api)' : '') . ' ' . ($y['from'] ?? '') . ': ' . mb_substr((string)$y['t'], 0, 80), array_slice(tgu_log($self), -10));
$o['desk_seen'] = json_decode((string)meta_get('tgu_desk_seen'), true);
$o['events'] = array_map(fn($r) => gmdate('H:i:s', intdiv((int)$r['at'], 1000)) . ' ' . $r['type'], q("SELECT type, at FROM wag_events WHERE type LIKE 'tg.%' ORDER BY at DESC LIMIT 12")->fetchAll());
$o['recent_chats'] = []; foreach (tgu_chats() as $id => $x) if ((int)($x['at'] ?? 0) > time() - 7200) $o['recent_chats'][] = [$id, mb_substr((string)($x['title'] ?? ''), 0, 30), $x['type'] ?? '', $x['mode'] ?? '', date('H:i', (int)$x['at'])];
$o['errors'] = array_map(fn($g) => [$g['where'], (int)$g['n'], $g['last'], mb_substr((string)$g['msg'], 0, 160)], array_slice(log_tail(data_dir() . '/server-errors.log', 7200)['groups'], 0, 6));
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n";
