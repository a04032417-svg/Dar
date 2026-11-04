<?php
// The partners' kit again, with the offer the owner set today (2026-11-04): plans from $100 a month (50% off until 11 November), 5 free days.
// Delivered inside each active marketer's dashboard, once (meta flag pk2_<tid>).
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$site = rtrim(site_url(), '/'); $res = ['ok' => 0, 'skip' => 0]; $now = now_ms();
foreach (q("SELECT id, rec FROM tenants WHERE app = 'partner' AND status = 'active'")->fetchAll() as $r) {
  $T = json_decode((string)$r['rec']) ?: new stdClass(); $code = (string)($T->code ?? ''); $tid = (string)$r['id'];
  if ($code === '' || !empty($T->demo) || !empty($T->reviewer)) { $res['skip']++; continue; }
  if (meta_get('pk2_' . $tid) !== null) { $res['skip']++; continue; }
  $lg = in_array((string)($T->demo_lang ?? ''), ['ar', 'en', 'fr'], true) ? $T->demo_lang : 'ar';
  $refA = "$site/app/?ref=$code"; $refB = "$site/builders/?ref=$code";
  if ($lg === 'fr') { $title = 'Nouvelle offre : à partir de 100 $ par mois jusqu\'au 11 novembre'; $body = "Les formules démarrent maintenant à 100 \$ par mois (-50 % jusqu'au 11 novembre) et les 5 premiers jours sont gratuits — un argument simple pour vos contacts.\n\nVos liens :\n• Agences : $refA\n• Promoteurs : $refB\n\nMessage prêt à envoyer :\n« Dar gère toute une agence immobilière (biens, clients, visites, offres, site) en arabe, anglais et français. Essayez la démo sans inscription : $refA — à partir de 100 \$/mois jusqu'au 11 novembre, 5 jours gratuits. »"; }
  elseif ($lg === 'en') { $title = 'New offer: from $100 a month until 11 November'; $body = "Plans now start at \$100 a month (50% off until 11 November) and the first 5 days are free — a simple argument for your contacts.\n\nYour links:\n• Agencies: $refA\n• Developers: $refB\n\nReady-to-send message:\n\"Dar runs a whole real-estate agency (listings, clients, viewings, offers, website) in Arabic, English and French. Try the demo, no sign-up: $refA — from \$100/month until 11 November, 5 days free.\""; }
  else { $title = 'عرض جديد: من 100$ شهريًا حتى 11 نوفمبر'; $body = "الباقات تبدأ الآن من 100\$ شهريًا (خصم 50% حتى 11 نوفمبر) وأول 5 أيام مجانًا — حجة بسيطة لمعارفك.\n\nروابطك:\n• للمكاتب: $refA\n• للمطوّرين: $refB\n\nرسالة جاهزة للإرسال:\n«دار» تدير مكتبًا عقاريًا كاملًا (عقارات، عملاء، معاينات، عروض، موقع) بالعربية والإنجليزية والفرنسية. جرّب التجربة دون تسجيل: $refA — من 100\$ شهريًا حتى 11 نوفمبر، و5 أيام مجانًا."; }
  $k = 'db:' . $tid . ':notices'; $ns = jdec(kv_get($tid, $k)); if (!is_array($ns)) $ns = [];
  array_unshift($ns, (object)['id' => 'n' . base_convert((string)$now, 10, 36) . bin2hex(random_bytes(3)), 'created' => iso($now), 'code' => '', 'vars' => (object)[], 'title' => $title, 'body' => $body, 'level' => 'good', 'link' => 'p_commissions', 'read' => false]);
  try { kv_put($tid, $k, jenc(array_slice($ns, 0, 300)), 'platform'); meta_set('pk2_' . $tid, (string)$now); $res['ok']++; } catch (Throwable $e) { $res['skip']++; }
}
echo json_encode($res, JSON_UNESCAPED_UNICODE);
