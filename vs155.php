<?php
// A read-only look after 3.33.98: the desk knows his account and chats; its AI answers a question in the right shape (nothing is run or sent).
ini_set('display_errors', '1'); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'self' => tgu_self_id() !== '', 'open' => tgu_open(), 'persona' => tgu_persona()['on'] ?? null];
$p = tgu_desk_prompt(); preg_match('/HIS CHATS[^\n]*\n((?:c\d+ ·[^\n]*\n?)*)/u', $p, $m); $o['chats'] = array_map(fn($l) => mb_substr($l, 0, 70), array_values(array_filter(explode("\n", $m[1] ?? ''))));
$c = ['dev' => true, 'public' => false, 'ns' => 'platform', 'tenant' => null, 'trow' => null, 'user' => ['id' => 'tgu-desk', 'lang' => 'ar'], 'manager' => false];
try { $r = ai_complete($c, ['fn' => 'tgu.desk', 'messages' => [['role' => 'system', 'content' => $p], ['role' => 'user', 'content' => "HIS ORDER NOW:\nوش المجموعات اللي أنا فيها؟"]], 'json' => true, 'soft_json' => true, 'tier' => 'standard', 'strong' => true, 'temperature' => 0.3, 'max_tokens' => 600, 'deadline' => 30, 'cache' => false]); $o['ai'] = tgu_desk_parse($r); $o['ai']['reply'] = mb_substr($o['ai']['reply'], 0, 300); }
catch (Throwable $e) { $o['ai_err'] = $e instanceof DarFail ? $e->darCode : $e->getMessage(); }
$o['errors'] = array_map(fn($g) => [$g['where'], (int)$g['n'], $g['last'], mb_substr((string)$g['msg'], 0, 100)], array_slice(log_tail(data_dir() . '/server-errors.log', 3600)['groups'], 0, 5));
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n";
