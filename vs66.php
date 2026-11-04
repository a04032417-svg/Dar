<?php
// Dar partner activation (the owner, 2026-11-04: «منظومات متعددة المصادر بلا قناة واحدة»): arm every active marketer with a ready selling kit
// — their own referral links, the live offer, and copy they can paste to their own audience — delivered INSIDE their Dar dashboard (no email,
// no sending limit). Each partner is an independent channel reaching people Dar never emails. Pushed once (meta flag per partner).
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$site = rtrim(site_url(), '/'); $res = ['ok' => 0, 'skip' => 0]; $now = now_ms();
foreach (q("SELECT id, rec FROM tenants WHERE app = 'partner' AND status = 'active'")->fetchAll() as $r) {
  $T = json_decode((string)$r['rec']) ?: new stdClass(); $code = (string)($T->code ?? ''); $tid = (string)$r['id'];
  if ($code === '' || !empty($T->demo) || !empty($T->reviewer)) { $res['skip']++; continue; }
  if (meta_get('pk_' . $tid) !== null) { $res['skip']++; continue; } // once
  $lg = in_array((string)($T->demo_lang ?? ''), ['ar', 'en', 'fr'], true) ? $T->demo_lang : 'ar';
  $refA = "$site/app/?ref=$code"; $refB = "$site/builders/?ref=$code";
  if ($lg === 'fr') {
    $title = 'Votre kit partenaire Dar est prêt — commencez à gagner';
    $body = "Votre lien partenaire (vous touchez une commission récurrente sur chaque abonnement pris via lui) :\n• Agences : $refA\n• Promoteurs : $refB\n\nLes formules sont à -50 % en ce moment et les 2 premiers jours sont gratuits — un bon moment pour inviter vos contacts.\n\nMessage prêt à envoyer :\n« Dar gère toute une agence immobilière (biens, clients, visites, offres, site) en arabe, anglais et français. Essayez la démo sans inscription : $refA »\n\nVos commissions et votre paiement : page Commissions.";
  } elseif ($lg === 'en') {
    $title = 'Your Dar partner kit is ready — start earning';
    $body = "Your partner link (you earn a recurring commission on every subscription taken through it):\n• Agencies: $refA\n• Developers: $refB\n\nPlans are 50% off right now and the first 2 days are free — a good moment to invite your contacts.\n\nReady-to-send message:\n\"Dar runs a whole real-estate agency (listings, clients, viewings, offers, website) in Arabic, English and French. Try the demo, no sign-up: $refA\"\n\nYour commissions and payout: Commissions page.";
  } else {
    $title = 'جهّزنا لك عدّة الشريك — ابدأ الكسب الآن';
    $body = "رابطك كشريك (تكسب عمولة متكررة عن كل اشتراك يتم عبره):\n• للمكاتب: $refA\n• للمطوّرين: $refB\n\nالباقات عليها خصم 50% الآن وأول يومين مجانًا — وقت مناسب لدعوة معارفك.\n\nرسالة جاهزة للإرسال:\n«دار» تدير مكتبًا عقاريًا كاملًا (عقارات، عملاء، معاينات، عروض، موقع) بالعربية والإنجليزية والفرنسية. جرّب التجربة دون تسجيل: $refA\n\nعمولاتك وصرفها: صفحة العمولات.";
  }
  $k = 'db:' . $tid . ':notices'; $ns = jdec(kv_get($tid, $k)); if (!is_array($ns)) $ns = [];
  array_unshift($ns, (object)['id' => 'n' . base_convert((string)$now, 10, 36) . bin2hex(random_bytes(3)), 'created' => iso($now), 'code' => '', 'vars' => (object)[], 'title' => $title, 'body' => $body, 'level' => 'good', 'link' => 'p_commissions', 'read' => false]);
  try { kv_put($tid, $k, jenc(array_slice($ns, 0, 300)), 'platform'); meta_set('pk_' . $tid, (string)$now); $res['ok']++; } catch (Throwable $e) { $res['skip']++; }
}
echo json_encode($res, JSON_UNESCAPED_UNICODE);
