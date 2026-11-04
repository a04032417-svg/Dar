<?php
// Dar outreach wave 3 (the owner's full approval, 2026-11-04: «ركز على جميع العالم»): agencies in 25+ countries — each one's public contact address read from its own site,
// then one introduction in its language (English, French or Arabic) from Dar's mailbox. Each address once (sent.json); a reply «stop» is honoured by hand.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$L = array_values(array_filter(array_map('trim', file(__DIR__ . '/leads57.txt'))));
$paths = ['', '/contact', '/contact-us', '/contact/', '/nous-contacter', '/contactez-nous'];
$jobs = []; foreach ($L as $i => $l) { [$lg, $n, $d] = explode('|', $l); foreach ($paths as $p) $jobs[] = [$i, 'https://' . $d . $p]; }
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
$pref = ['info', 'contact', 'hello', 'office', 'sales', 'enquiries', 'enquiry', 'admin', 'agence', 'bonjour'];
$T = [
  'en' => ['Run {n} on one platform — see the demo agency', "Hello {n} team,\n\nDar (dar.ink) runs a real-estate agency in one place: listings, clients, viewings, offers, email campaigns and your own website — in English, Arabic or French, for any country.\n\nSee a complete demo agency now, no sign-up:\nhttps://dar.ink/app/?example=1\n\nPlans are 50% off today, and the first 2 days are free before any charge: https://dar.ink\n\nIf this is not for you, reply \"stop\" and we will not write again.\n\nDar team\nservice@dar.ink\n"],
  'fr' => ['Gérez {n} sur une seule plateforme — voyez l’agence de démonstration', "Bonjour l’équipe {n},\n\nDar (dar.ink) gère une agence immobilière en un seul endroit : biens, clients, visites, offres, campagnes e-mail et votre propre site — en français, anglais ou arabe, pour tout pays.\n\nVoyez une agence de démonstration complète, sans inscription :\nhttps://dar.ink/app/?example=1\n\nLes formules sont à -50 % aujourd’hui, et les 2 premiers jours sont gratuits avant tout prélèvement : https://dar.ink\n\nSi cela ne vous concerne pas, répondez « stop » et nous ne vous écrirons plus.\n\nL’équipe Dar\nservice@dar.ink\n"],
  'ar' => ['نظام إدارة {n} العقاري — جرّبوه الآن دون تسجيل', "السلام عليكم فريق {n}،\n\n«دار» (dar.ink) منصة لإدارة المكاتب العقارية: العقارات والعملاء والمواعيد والعروض وحملات البريد وموقع المكتب الخاص في مكان واحد، بالعربية والإنجليزية والفرنسية ولأي دولة.\n\nتصفّحوا مكتبًا تجريبيًا كاملًا الآن دون تسجيل:\nhttps://dar.ink/app/?example=1\n\nالباقات عليها خصم 50% اليوم، وأول يومين مجانًا قبل أي خصم من البطاقة: https://dar.ink\n\nإن لم يناسبكم هذا ردّوا بكلمة «إيقاف» ولن نراسلكم مرة أخرى.\n\nفريق دار\nservice@dar.ink\n"],
];
$res = ['ok' => 0, 'none' => 0, 'fail' => [], 'skip' => 0, 'by' => []];
foreach ($L as $i => $l) { [$lg, $n, $d] = explode('|', $l);
  $ems = array_keys($found[$i] ?? []); $base = explode('.', $d)[0];
  $ems = array_values(array_filter($ems, fn($e) => strpos(explode('@', $e)[1], $base) !== false || in_array(explode('@', $e)[1], ['gmail.com', 'outlook.com', 'hotmail.com', 'yahoo.com'], true)));
  if (!$ems) { $res['none']++; continue; }
  usort($ems, fn($a, $b) => ((($x = array_search(explode('@', $a)[0], $pref, true)) === false ? 9 : $x) <=> (($y = array_search(explode('@', $b)[0], $pref, true)) === false ? 9 : $y)));
  $to = $ems[0]; if (isset($sent[$to])) { $res['skip']++; continue; }
  [$s, $b] = $T[$lg]; $ok = send_mail($to, str_replace('{n}', $n, $s), str_replace('{n}', $n, $b), 'campaign', 'platform', false, ['reply' => 'service@dar.ink', 'name' => 'Dar | دار']);
  if ($ok) { $res['ok']++; $res['by'][$lg] = ($res['by'][$lg] ?? 0) + 1; $sent[$to] = ['g', gmdate('c')]; } else $res['fail'][] = [$d, (string)($GLOBALS['dar_mail_err'] ?? '')];
  file_put_contents($keep, json_encode($sent)); usleep(2500000);
}
$res['total'] = count($L); echo json_encode($res, JSON_UNESCAPED_UNICODE);
