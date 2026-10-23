<?php
// One-time (2026-10-23, the owner's note 17 — «بريد المنصة (SMTP): رُفض اسم المستخدم أو كلمة المرور»; «ما لم يُرفق فحل الموضوع أنت»):
// the platform's mail leaves from a mailbox made for Dar on the host, no-reply@dar.ink, and replies go to service@dar.ink (his own
// mailbox, untouched). The password is given on the command line only — never in this file. It is tried with a real message first;
// nothing is saved unless the mailbox accepts it. Then the settings and the vault are written, a message goes through Dar's own path,
// and the requirements board's check is run so its card says what is true now.
$W = '/home/u791571885/domains/dar.ink/public_html'; chdir($W); require $W . '/dar-sync.php';
$done = (string)(meta_get('mail_box_3331') ?? ''); if ($done !== '') { echo json_encode(['ok' => true, 'done_before' => $done, 'user' => mail_cfg()['smtp_user']]), "\n"; exit(0); } // (a cron job runs each minute: once only)
$pass = (string)($argv[1] ?? ''); if (strlen($pass) < 12) { echo "no password\n"; exit(2); }
$box = 'no-reply@dar.ink'; $reply = 'service@dar.ink';
$cfg = mail_cfg(); $before = ['user' => $cfg['smtp_user'], 'host' => $cfg['smtp_host'], 'port' => $cfg['smtp_port'], 'reply_to' => $cfg['reply_to'], 'from' => $cfg['from_email']];
$cfg['smtp_host'] = 'smtp.hostinger.com'; $cfg['smtp_port'] = 465; $cfg['smtp_secure'] = 'ssl'; $cfg['smtp_user'] = $box; $cfg['from_email'] = ''; if (trim((string)$cfg['reply_to']) === '') $cfg['reply_to'] = $reply;
$body = "هذه رسالة من منصة دار: بريد المنصة يخرج الآن من صندوقه الخاص (no-reply@dar.ink)، والردود تصل إلى service@dar.ink.\nThis message is from Dar: the platform's mail now leaves from its own mailbox.\n";
try { smtp_send($cfg, $pass, $box, brand_name(), $reply, $reply, 'دار — بريد المنصة يعمل', $body); }
catch (Throwable $e) { echo json_encode(['ok' => false, 'step' => 'try', 'err' => mail_why($e->getMessage()), 'before' => $before], JSON_UNESCAPED_UNICODE), "\n"; exit(1); }
meta_set('mail_cfg', jenc($cfg)); vault_put('smtp_password', $pass); meta_set('mail_box_3331', iso());
$sent = send_mail($reply, 'دار — اختبار البريد عبر مسار المنصة', $body, 'test', '', true); $via = (string)($GLOBALS['dar_mail_via'] ?? ''); $err = (string)($GLOBALS['dar_mail_err'] ?? '');
$chk = null; if (function_exists('rq_check') && function_exists('rq_owner_ctx') && ($oc = rq_owner_ctx())) { try { $chk = rq_check($oc, 'email_smtp', 'save'); } catch (Throwable $e) { $chk = ['err' => $e->getMessage()]; } }
echo json_encode(['ok' => true, 'before' => $before, 'now' => ['user' => $box, 'reply_to' => $cfg['reply_to']], 'sent' => $sent, 'via' => $via, 'err' => $err, 'check' => is_array($chk) ? array_intersect_key($chk, array_flip(['ok', 'code', 'msg', 'err'])) : null], JSON_UNESCAPED_UNICODE), "\n";
