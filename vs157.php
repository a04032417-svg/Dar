<?php
// A look at why «فرح» stopped answering: the account's state, each active chat's latest lines, one test message to his own «الرسائل المحفوظة» only.
ini_set('display_errors', '1'); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$self = tgu_self_id(); $o = ['state' => tgu_state(), 'persona' => tgu_persona()];
foreach (tgu_chats() as $id => $x) { if ((int)($x['at'] ?? 0) < time() - 10800) continue; $o['chats'][(string)$id] = [mb_substr((string)($x['title'] ?? ''), 0, 25), $x['mode'] ?? '', array_map(fn($y) => gmdate('H:i:s', intdiv((int)$y['at'], 1000)) . (!empty($y['out']) ? ' OUT' : ' IN') . (!empty($y['api']) ? '(api)' : '') . ': ' . mb_substr((string)$y['t'], 0, 50), array_slice(tgu_log((string)$id), -5))]; }
$o['test_send'] = tgu_send($self, '⚙️ فحص من دار: الإرسال من حسابك يعمل.', 0, 'probe157-' . gmdate('YmdHi'), false);
$o['errors'] = array_map(fn($g) => [$g['where'], (int)$g['n'], $g['last'], mb_substr((string)$g['msg'], 0, 160)], array_slice(log_tail(data_dir() . '/server-errors.log', 10800)['groups'], 0, 6));
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n";
