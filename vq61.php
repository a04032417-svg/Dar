<?php
// read-only look at the platform's mail now + one test message to the developer's own address (the owner's inbox), to see which way mail leaves
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['now' => gmdate('c')];
foreach (q("SELECT at, kind, ok, via, err FROM mail_log WHERE kind != 'campaign' ORDER BY id DESC LIMIT 25")->fetchAll() as $r) $o['log'][] = [gmdate('H:i:s', (int)($r['at'] / 1000)), $r['kind'], (int)$r['ok'], $r['via'], mb_substr((string)$r['err'], 0, 90)];
ev_db(); foreach (q("SELECT app, ok, via, err, retry, sent_at, exp FROM email_checks ORDER BY sent_at DESC LIMIT 15")->fetchAll() as $r) $o['checks'][] = [$r['app'], (int)$r['ok'], $r['via'], mb_substr((string)$r['err'], 0, 80), (int)$r['retry'], gmdate('m-d H:i', (int)($r['sent_at'] / 1000))];
$dev = one("SELECT email FROM users WHERE app = 'developer' ORDER BY created LIMIT 1"); $to = (string)($dev['email'] ?? '');
$o['to'] = ev_mask($to);
if ($to !== '') { $ok = send_mail($to, 'اختبار وصول بريد دار ' . gmdate('H:i'), "رسالة اختبار من منصة دار للتحقق من وصول رموز التأكيد.\n", 'doctor', 'platform', true);
  $o['test'] = ['ok' => $ok, 'via' => $GLOBALS['dar_mail_via'] ?? '', 'err' => (string)($GLOBALS['dar_mail_err'] ?? '')]; }
echo json_encode($o, JSON_UNESCAPED_UNICODE);
