<?php
// Dar outreach wave 2 (the owner's full approval, 2026-11-04): one WhatsApp message to each listed office that publishes a WhatsApp number on its own site,
// from Dar's own WhatsApp (replies reach Dar's assistant). Spaced out; each number once (sent.json).
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php'; require_once $R . '/dar-wa.php';
$row = wag_row(wag_sid('platform')); if (!$row || $row['status'] !== 'open') { echo json_encode(['error' => 'platform whatsapp not open', 'status' => $row['status'] ?? 'none']); exit; }
$keep = __DIR__ . '/sent.json'; $sent = is_file($keep) ? (json_decode((string)file_get_contents($keep), true) ?: []) : [];
$src = json_decode((string)@file_get_contents('/home/u791571885/dgq51/out.txt'), true)['leads'] ?? [];
$ar = "السلام عليكم فريق {n} 👋\nمعكم فريق «دار» (dar.ink)، منصة لإدارة المكاتب العقارية: العقارات والعملاء والمواعيد والعروض والتسويق في مكان واحد.\nجهّزنا لكم مكتبًا تجريبيًا كاملًا تتصفحونه الآن دون تسجيل:\nhttps://dar.ink/app/?example=1\nالباقات عليها الآن خصم 50% مع يومين مجانًا قبل أي خصم.\nأي سؤال اكتبوه هنا ونرد عليكم مباشرة. ولو ما يناسبكم أرسلوا «إيقاف» ولن نراسلكم مرة أخرى.";
$en = "\n\nHello {n} team — Dar (dar.ink) runs a real-estate office in one place. Full demo office, no sign-up: https://dar.ink/app/?example=1 — 50% off now, first 2 days free. Reply here with any question, or \"stop\".";
$out = ['ok' => [], 'fail' => [], 'skip' => 0]; $n = 0;
foreach ($src as [$cc, $name, $dom, $items]) foreach ($items as $it) {
  if (strpos($it, 'wa:') !== 0) continue; $p = preg_replace('/\D/', '', substr($it, 3)); if (strpos($p, '00') === 0) $p = substr($p, 2);
  if ($cc === 'SA' && preg_match('/^05\d{8}$/', $p)) $p = '966' . substr($p, 1);
  if (preg_match('/^(9200|800|966920|971800)/', $p) || strlen($p) < 10) { $out['skip']++; continue; }
  if (isset($sent[$p])) { $out['skip']++; continue; }
  $text = str_replace('{n}', $name, $ar . ($cc === 'SA' ? '' : $en));
  $r = wag_send('platform', $p, $text, ['typing' => true]);
  if (($r['status'] ?? '') === 'sent') { $out['ok'][] = $cc . ':' . substr($p, -4); $sent[$p] = gmdate('c'); } else $out['fail'][] = [$cc . ':' . substr($p, -4), $r['error'] ?? ''];
  file_put_contents($keep, json_encode($sent)); $n++; sleep(random_int(25, 50));
  break; // one number per office
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
