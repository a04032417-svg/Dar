<?php
// Dar outreach wave 5 (the owner's approval, 2026-11-04 «نعم»): one personal note to the founder or CEO of 15 brokerages and developers in the Gulf
// (one person per company, addresses from Vibe Prospecting marked valid). The list is in this repository only encrypted; its key is given on the
// command line and is never here. Each address once; a reply «stop» is honoured by hand; stops at the first message the mailbox refuses.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$raw = (string)file_get_contents(__DIR__ . '/leads64.enc'); $key = base64_decode((string)($argv[1] ?? ''));
$L = json_decode((string)openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16)), true);
if (!is_array($L)) { echo json_encode(['error' => 'decrypt']); exit; }
$keep = __DIR__ . '/sent.json'; $sent = is_file($keep) ? (json_decode((string)file_get_contents($keep), true) ?: []) : [];
$prev = []; foreach (glob('/home/u791571885/dgs5*/sent.json') ?: [] as $pf) if ($pf !== $keep) foreach ((json_decode((string)file_get_contents($pf), true) ?: []) as $e => $_) $prev[$e] = 1;
$arn = ['Ismail' => 'إسماعيل', 'Rakan' => 'راكان', 'Saleh' => 'صالح', 'Amin' => 'أمين'];
$res = ['ok' => 0, 'skip' => 0, 'fail' => []];
foreach ($L as $x) {
  $to = strtolower((string)$x['e']); if (!email_ok($to) || isset($sent[$to]) || isset($prev[$to])) { $res['skip']++; continue; }
  $demo = $x['k'] === 'b' ? 'https://dar.ink/builders/?example=1' : 'https://dar.ink/app/?example=1'; $c = (string)$x['c'];
  if ($x['lg'] === 'ar') {
    $n = $arn[$x['f']] ?? $x['f'];
    $s = $x['k'] === 'b' ? "{$c}: مبيعات مشاريعكم ووحداتها في نظام واحد" : "{$c}: نظام واحد لعقارات المكتب وعملائه وعروضه";
    $b = "مرحبًا {$n}،\n\n" . ($x['k'] === 'b' ? "«دار» (dar.ink) منصة تجمع للمطوّر مشاريعه ووحداته (المباعة والمحجوزة والمتاحة) والمشترين وجداول الدفعات والوسطاء الذين يبيعون، بالعربية والإنجليزية." : "«دار» (dar.ink) منصة تجمع للمكتب العقاري العقارات والعملاء والمعاينات والعروض وحملات البريد وموقع المكتب الخاص، بالعربية والإنجليزية.")
       . "\n\nيمكنك فتح نسخة تجريبية كاملة الآن دون تسجيل:\n{$demo}\n\nالباقات عليها خصم 50% حاليًا، وأول يومين مجانًا قبل أي خصم من البطاقة.\n\nإن لم يكن هذا مناسبًا ردّ بكلمة «إيقاف» ولن أراسلك مرة أخرى.\n\nفريق دار\nservice@dar.ink\n";
  } else {
    $s = $x['k'] === 'b' ? "{$c}: projects, units and buyers in one place (demo, no sign-up)" : "{$c}: one system for listings, leads and offers (demo, no sign-up)";
    $b = "Hi {$x['f']},\n\n" . ($x['k'] === 'b' ? "Dar (dar.ink) gives a developer one place for its projects and units (sold, reserved, available), buyers, payment schedules and the brokers who sell them, in English and Arabic." : "Dar (dar.ink) runs a brokerage in one place: listings, leads, viewings, offers, email campaigns and the office's own website, in English and Arabic.")
       . "\n\nYou can open a complete demo now, without signing up:\n{$demo}\n\nPlans are 50% off at the moment, and the first 2 days are free before any charge.\n\nIf this isn't relevant, reply \"stop\" and I won't write again.\n\nDar team\nservice@dar.ink\n";
  }
  $ok = send_mail($to, $s, $b, 'campaign', 'platform', false, ['reply' => 'service@dar.ink', 'name' => 'Dar | دار']);
  if ($ok && ($GLOBALS['dar_mail_via'] ?? '') !== 'smtp') { $sent[$to] = 'plain'; file_put_contents($keep, json_encode($sent)); $res['stopped'] = 'mailbox refused'; break; }
  if ($ok) { $res['ok']++; $sent[$to] = gmdate('c'); } else { $res['fail'][] = (string)($GLOBALS['dar_mail_err'] ?? ''); if (count($res['fail']) >= 3) break; }
  file_put_contents($keep, json_encode($sent)); usleep(3000000);
}
$res['total'] = count($L); echo json_encode($res, JSON_UNESCAPED_UNICODE);
