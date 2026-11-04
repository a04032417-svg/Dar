<?php
// Dar outreach wave 4 (the owner's full approval, 2026-11-04: «اكمل طرق واستراتيجيات إضافية»): distribution partners, not single offices — agent associations, developer federations,
// real-estate academies (a partner link that pays them on every member who subscribes) and real-estate trade press (a story idea). Public organisation addresses only, each once;
// addresses already written to in waves 1 and 3 are skipped; a reply «stop» is honoured by hand. At most 35 a day: the mailbox's daily limit (100) is shared with sign-in codes.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$L = array_values(array_filter(array_map('trim', file(__DIR__ . '/leads58.txt'))));
$paths = ['', '/contact', '/contact-us', '/about', '/about-us', '/nous-contacter', '/contactez-nous', '/advertise'];
$jobs = []; foreach ($L as $i => $l) { [$lg, $kd, $n, $d] = explode('|', $l); foreach ($paths as $p) $jobs[] = [$i, 'https://' . $d . $p]; }
$found = []; $bad = '/\.(png|jpe?g|gif|svg|webp|css|js)$|example\.|sentry|wixpress|domain\.com|email\.com|yourmail|godaddy|@2x|you@company|^jobs@|^careers@|^hr@|^webmaster@|%20|privacy@|noreply|no-reply/i';
foreach (array_chunk($jobs, 30) as $chunk) {
  $mh = curl_multi_init(); $hs = [];
  foreach ($chunk as $k => [$i, $u]) { $h = curl_init($u); curl_setopt_array($h, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_MAXREDIRS => 4, CURLOPT_TIMEOUT => 12, CURLOPT_CONNECTTIMEOUT => 6, CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124 Safari/537.36', CURLOPT_MAXFILESIZE => 3000000]); curl_multi_add_handle($mh, $h); $hs[$k] = [$h, $i]; }
  do { $st = curl_multi_exec($mh, $run); if ($run) curl_multi_select($mh, 1.0); } while ($run && $st == CURLM_OK);
  foreach ($hs as [$h, $i]) { $b = html_entity_decode((string)curl_multi_getcontent($h)); curl_multi_remove_handle($mh, $h); curl_close($h);
    if (preg_match_all('/([A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,})/', $b, $m)) foreach ($m[1] as $e) { $e = strtolower(trim($e, '.')); if (!preg_match($bad, $e) && email_ok($e)) $found[$i][$e] = 1; } }
  curl_multi_close($mh);
}
$keep = __DIR__ . '/sent.json'; $sent = is_file($keep) ? (json_decode((string)file_get_contents($keep), true) ?: []) : [];
$prev = [];
foreach (['/home/u791571885/dgs53/sent.json', '/home/u791571885/dgs57/sent.json'] as $pf) if (is_file($pf)) foreach ((json_decode((string)file_get_contents($pf), true) ?: []) as $e => $_) $prev[$e] = 1;
$pref = ['partnerships', 'partners', 'membership', 'members', 'info', 'contact', 'hello', 'office', 'admin', 'marketing', 'news', 'editor', 'editorial', 'tips', 'press', 'contact-fnaim'];
$stopEn = "\nIf this is not for you, reply \"stop\" and we will not write again.\n\nDar team\nservice@dar.ink\n";
$stopFr = "\nSi cela ne vous concerne pas, répondez « stop » et nous ne vous écrirons plus.\n\nL’équipe Dar\nservice@dar.ink\n";
$stopAr = "\nإن لم يناسبكم هذا ردّوا بكلمة «إيقاف» ولن نراسلكم مرة أخرى.\n\nفريق دار\nservice@dar.ink\n";
$what = ['o' => ['en' => 'members', 'fr' => 'adhérents', 'ar' => 'أعضائكم'], 't' => ['en' => 'trainees and graduates', 'fr' => 'stagiaires', 'ar' => 'متدربيكم وخريجيكم'], 'b' => ['en' => 'member developers', 'fr' => 'promoteurs adhérents', 'ar' => 'مطوريكم الأعضاء']];
function dar_tpl($lg, $kd, $n, $who, $stopEn, $stopFr, $stopAr) {
  $demo = $kd === 'b' ? 'https://dar.ink/builders/?example=1' : 'https://dar.ink/app/?example=1';
  $prod = ['en' => $kd === 'b' ? "a developer's projects and units (sold, reserved, available), buyers, payment schedules and the brokers who sell the units" : "a real-estate office: listings, clients, viewings, offers, email campaigns and the office's own website",
           'fr' => $kd === 'b' ? "les programmes et lots d’un promoteur (vendus, réservés, disponibles), les acquéreurs, les échéanciers et les agents qui vendent" : "une agence immobilière : biens, clients, visites, offres, campagnes e-mail et le site de l’agence",
           'ar' => $kd === 'b' ? 'مشاريع المطوّر ووحداته (المباعة والمحجوزة والمتاحة) والمشترين وجداول الدفعات والوسطاء الذين يبيعون الوحدات' : 'المكتب العقاري: العقارات والعملاء والمعاينات والعروض وحملات البريد وموقع المكتب الخاص'];
  if ($kd === 'm') {
    if ($lg === 'fr') return ['Idée de sujet : une plateforme qui gère toute une agence immobilière, en 3 langues', "Bonjour l’équipe {$n},\n\nUne idée de sujet pour vos lecteurs : Dar (dar.ink) est une nouvelle plateforme web qui gère une agence immobilière de bout en bout — biens, clients, visites, offres, campagnes e-mail et site de l’agence — ainsi qu’un espace pour les promoteurs (programmes, lots, acquéreurs, échéanciers, agents). Elle fonctionne en français, anglais et arabe, pour tout pays et toute devise.\n\nChacun peut ouvrir une agence de démonstration complète sans inscription : https://dar.ink/app/?example=1\nLa démonstration promoteurs : https://dar.ink/builders/?example=1\n\nNous répondons volontiers à vos questions et pouvons ouvrir un accès complet pour un test. Il suffit de répondre à cet e-mail.\n" . $stopFr];
    return ['Story idea: one platform that runs a whole real-estate office, in 3 languages', "Hello {$n} team,\n\nA short story idea for your readers: Dar (dar.ink) is a new web platform that runs a real-estate office end to end — listings, clients, viewings, offers, email campaigns and the office's own website — with a matching workspace for developers (projects, units, buyers, payment schedules, brokers). It works in English, Arabic and French, for any country and currency.\n\nAnyone can open a complete demo office without signing up: https://dar.ink/app/?example=1\nThe developers' demo: https://dar.ink/builders/?example=1\n\nWe are happy to answer questions or open full access for a review. Just reply to this email.\n" . $stopEn];
  }
  if ($lg === 'ar') return ["شراكة مع {$n}: ميزة جاهزة ل{$who} ودخل متكرر لكم", "السلام عليكم فريق {$n}،\n\nبنينا «دار» (dar.ink)، منصة تدير {$prod['ar']} في مكان واحد، بالعربية والإنجليزية والفرنسية ولأي دولة وعملة.\n\nنقترح عليكم شراكة بسيطة:\n• يمكن ل{$who} تجربة نسخة كاملة فورًا دون تسجيل: {$demo}\n• تفتحون حساب شريك مجاني وتحصلون على عمولة متكررة عن كل مشترك يأتي عبر رابطكم: https://dar.ink/partner/\n• الباقات عليها خصم 50% الآن، وأول يومين مجانًا قبل أي خصم من البطاقة.\n\nإن ناسبكم ذكرها في نشرتكم أو في إحدى دوراتكم، ردّوا على هذه الرسالة ونجهّز لكم المادة.\n" . $stopAr];
  if ($lg === 'fr') return ["Un avantage pour les {$who} de {$n} — et un revenu récurrent pour vous", "Bonjour l’équipe {$n},\n\nNous avons conçu Dar (dar.ink), une plateforme qui gère {$prod['fr']} en un seul endroit — en français, anglais et arabe, pour tout pays.\n\nNous vous proposons un partenariat simple :\n• Vos {$who} essaient une démonstration complète tout de suite, sans inscription : {$demo}\n• {$n} ouvre un compte partenaire gratuit et touche une commission récurrente sur chaque abonnement pris via son lien : https://dar.ink/partner/\n• Les formules sont à -50 % en ce moment, et les 2 premiers jours sont gratuits avant tout prélèvement.\n\nSi une mention dans votre lettre aux adhérents ou une session de formation vous convient, répondez et nous préparons le contenu.\n" . $stopFr];
  return ["A ready benefit for {$n}'s {$who} — and recurring income for you", "Hello {$n} team,\n\nWe built Dar (dar.ink), a platform that runs {$prod['en']} in one place — in English, Arabic and French, for any country.\n\nWe would like to offer it to your {$who}:\n• They can open a complete demo right away, no sign-up: {$demo}\n• {$n} opens a free partner account and earns a recurring commission on every subscription that comes through its link: https://dar.ink/partner/\n• Plans are 50% off now, and the first 2 days are free before any charge.\n\nIf a short note in your newsletter or a training session suits you, reply and we will prepare the material.\n" . $stopEn];
}
$res = ['ok' => 0, 'none' => 0, 'fail' => [], 'skip' => 0, 'by' => []];
foreach ($L as $i => $l) { [$lg, $kd, $n, $d] = explode('|', $l); if ($res['ok'] >= 35) break;
  $ems = array_keys($found[$i] ?? []); $base = explode('.', $d)[0];
  $ems = array_values(array_filter($ems, fn($e) => strpos(explode('@', $e)[1], $base) !== false || in_array(explode('@', $e)[1], ['gmail.com', 'outlook.com', 'hotmail.com', 'yahoo.com'], true)));
  if (!$ems) { $res['none']++; continue; }
  usort($ems, fn($a, $b) => ((($x = array_search(explode('@', $a)[0], $pref, true)) === false ? 99 : $x) <=> (($y = array_search(explode('@', $b)[0], $pref, true)) === false ? 99 : $y)));
  $to = $ems[0]; if (isset($sent[$to]) || isset($prev[$to])) { $res['skip']++; continue; }
  $who = $what[$kd][$lg] ?? ''; [$s, $b] = dar_tpl($lg, $kd, $n, $who, $stopEn, $stopFr, $stopAr);
  $ok = send_mail($to, $s, $b, 'campaign', 'platform', false, ['reply' => 'service@dar.ink', 'name' => 'Dar | دار']);
  if ($ok) { $res['ok']++; $res['by'][$kd . $lg] = ($res['by'][$kd . $lg] ?? 0) + 1; $sent[$to] = [$kd, gmdate('c')]; } else { $res['fail'][] = [$d, (string)($GLOBALS['dar_mail_err'] ?? '')]; if (count($res['fail']) >= 3) break; }
  file_put_contents($keep, json_encode($sent)); usleep(2500000);
}
$res['total'] = count($L); echo json_encode($res, JSON_UNESCAPED_UNICODE);
