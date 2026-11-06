<?php
// A read-only look: the owner's Telegram chats (mode), the test group's latest messages, the persona. Nothing changed.
ini_set('display_errors', '0'); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$c = tgu_chats(); $out = ['persona' => tgu_persona(), 'tg_status' => json_decode((string)meta_get('tgu_status'), true), 'chats' => []];
foreach ($c as $id => $x) $out['chats'][] = [$id, $x['title'] ?? '', $x['type'] ?? '', $x['mode'] ?? ''];
$out['log'] = array_map(fn($y) => gmdate('H:i:s', intdiv((int)$y['at'], 1000)) . ' ' . ($y['out'] ? 'OUT' : 'IN') . (isset($y['api']) && $y['api'] ? '(api)' : '') . ' ' . $y['from'] . ': ' . mb_substr($y['t'], 0, 60), array_slice(tgu_log('-1004293981841'), -10));
$q = one("SELECT COUNT(*) n FROM meta WHERE k LIKE 'tgu_log:%'"); $out['logs'] = $q['n'] ?? 0;
$out['audit'] = array_map(fn($r) => $r['op'] . ' ' . $r['target'], q("SELECT op, target FROM audit WHERE op LIKE 'tg%' ORDER BY rowid DESC LIMIT 8")->fetchAll());
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
