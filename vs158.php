<?php
// A read-only look: the hourly counters that hold «فرح» back, per active chat. Nothing changed.
ini_set('display_errors', '1'); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['now' => gmdate('H:i:s')];
foreach (tgu_chats() as $id => $x) { if ((int)($x['at'] ?? 0) < time() - 10800) continue; $r = one('SELECT n, at FROM hits WHERE k = ?', [hash('sha256', 'tgu_chat:' . $id . '|*')]); $outs = count(array_filter(tgu_log((string)$id), fn($y) => !empty($y['api']) && (int)$y['at'] > (time() - 3600) * 1000));
  $o['c'][(string)$id] = [mb_substr((string)($x['title'] ?? ''), 0, 20), $x['type'] ?? '', $x['mode'] ?? '', $r ? ['n' => (int)$r['n'], 'since' => gmdate('H:i:s', (int)$r['at'])] : null, 'her_answers_1h' => $outs]; }
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n";
