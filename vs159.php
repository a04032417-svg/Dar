<?php
// After 3.33.99, once: one question to his desk exactly as Telegram delivers his words there (not «out», written by him) — its answer goes to his
// own «الرسائل المحفوظة» only. Then the version, and whether «فرح» is held back anywhere.
ini_set('display_errors', '1'); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$self = tgu_self_id(); $o = ['v' => SYNC_VERSION, 'is_desk' => tgu_is_desk($self, ['out' => false, 'by_api' => false, 'from' => ['id' => $self]], 'وش مجموعاتي؟')];
$o['event'] = tgu_event('tg.message.in', ['chat' => ['id' => $self, 'type' => 'private', 'title' => 'Farah'], 'id' => 0, 'text' => 'وش المجموعات اللي أنا فيها؟ (فحص من دار بعد التحديث)', 'out' => false, 'by_api' => false, 'from' => ['id' => $self, 'name' => 'Farah']]);
$o['desk_answer'] = array_map(fn($y) => gmdate('H:i:s', intdiv((int)$y['at'], 1000)) . ' ' . mb_substr((string)$y['t'], 0, 300), array_slice(array_values(array_filter(tgu_log($self), fn($y) => !empty($y['api']))), -1));
$o['errors'] = array_map(fn($g) => [$g['where'], (int)$g['n'], $g['last'], mb_substr((string)$g['msg'], 0, 120)], array_slice(log_tail(data_dir() . '/server-errors.log', 1800)['groups'], 0, 4));
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n";
