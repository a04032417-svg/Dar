<?php
// Dar's daily outreach engine (the owner, 2026-11-04: «منظومات تجلب العملاء بدون تدخل بشري»): every day it takes the next agencies from
// leads65.txt (public company domains only), reads each one's public contact address on its own site, and sends one introduction in its
// language — only inside the share of the mailbox that bulk mail may use (MAIL_BULK_SHARE), so the sign-in codes always keep their room.
// Each address once across all waves; a reply «stop» is honoured by hand; it stops at the first message the mailbox holds or refuses.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$D = __DIR__; $st = json_decode((string)@file_get_contents("$D/state.json"), true) ?: ['cursor' => 0, 'days' => 0, 'sent' => 0];
$log = function ($a) use ($D) { file_put_contents("$D/log.txt", gmdate('c') . ' ' . json_encode($a, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND); echo json_encode($a, JSON_UNESCAPED_UNICODE), "\n"; };
if (function_exists('mail_room_bulk') && ($h = mail_room_bulk()) !== '') { $log(['held' => $h, 'cursor' => $st['cursor']]); exit; }
$L = array_values(array_filter(array_map('trim', file("$D/leads65.txt"))));
if ($st['cursor'] >= count($L)) { $log(['done' => 'the list is finished', 'cursor' => $st['cursor']]); exit; }
$batch = array_slice($L, $st['cursor'], 70, true);
$paths = ['', '/contact', '/contact-us', '/about', '/nous-contacter', '/contactez-nous', '/contact.html'];
$jobs = []; foreach ($batch as $i => $l) { $d = explode('|', $l)[3]; foreach ($paths as $p) $jobs[] = [$i, 'https://' . $d . $p]; }
$found = []; $bad = '/\.(png|jpe?g|gif|svg|webp|css|js)$|example\.|sentry|wixpress|domain\.com|email\.com|yourmail|godaddy|@2x|^jobs@|^careers@|^hr@|^recruit|^webmaster@|%20|privacy@|noreply|no-reply|^dpo@|^rgpd@|^legal@/i';
foreach (array_chunk($jobs, 30) as $chunk) {
  $mh = curl_multi_init(); $hs = [];
  foreach ($chunk as $k => [$i, $u]) { $h = curl_init($u); curl_setopt_array($h, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_MAXREDIRS => 4, CURLOPT_TIMEOUT => 12, CURLOPT_CONNECTTIMEOUT => 6, CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124 Safari/537.36', CURLOPT_MAXFILESIZE => 3000000]); curl_multi_add_handle($mh, $h); $hs[$k] = [$h, $i]; }
  do { $s = curl_multi_exec($mh, $run); if ($run) curl_multi_select($mh, 1.0); } while ($run && $s == CURLM_OK);
  foreach ($hs as [$h, $i]) { $b = html_entity_decode((string)curl_multi_getcontent($h)); curl_multi_remove_handle($mh, $h); curl_close($h);
    if (preg_match_all('/([A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,})/', $b, $m)) foreach ($m[1] as $e) { $e = strtolower(trim($e, '.')); if (!preg_match($bad, $e) && email_ok($e)) $found[$i][$e] = 1; } }
  curl_multi_close($mh);
}
$keep = "$D/sent.json"; $sent = json_decode((string)@file_get_contents($keep), true) ?: [];
$prev = []; foreach (glob('/home/u791571885/dgs*/sent.json') ?: [] as $pf) if ($pf !== $keep) foreach ((json_decode((string)file_get_contents($pf), true) ?: []) as $e => $_) $prev[$e] = 1;
$pref = ['info', 'contact', 'hello', 'office', 'sales', 'enquiries', 'enquiry', 'admin', 'agence', 'bonjour', 'contactus'];
$U = 'https://darrealestatesoftware.wordpress.com/subscribe/';
$T = [
  'en' => ['o' => ['{n}: one system for listings, leads and offers (demo, no sign-up)', "Hello {n} team,\n\nDar (dar.ink) runs a real-estate agency in one place: listings, clients, viewings, offers, email campaigns and your own website, in English, Arabic or French, for any country.\n\nOpen a complete demo agency now, no sign-up:\nhttps://dar.ink/app/?example=1\n\nPlans are 50% off now, and the first 2 days are free before any charge.\n\nOr get one free template and one client-winning idea a week:\n$U\n\nIf this is not for you, reply \"stop\" and we will not write again.\n\nDar team\nservice@dar.ink\n"],
           'b' => ['{n}: projects, units and buyers in one place (demo, no sign-up)', "Hello {n} team,\n\nDar (dar.ink) gives a developer one place for its projects and units (sold, reserved, available), buyers, payment schedules and the brokers who sell them, in English, Arabic or French.\n\nOpen the developers' demo now, no sign-up:\nhttps://dar.ink/builders/?example=1\n\nPlans are 50% off now, and the first 2 days are free before any charge.\n\nOr get one free template and one client-winning idea a week:\n$U\n\nIf this is not for you, reply \"stop\" and we will not write again.\n\nDar team\nservice@dar.ink\n"]],
  'fr' => ['o' => ['{n} : biens, clients et offres sur une seule plateforme (démo sans inscription)', "Bonjour l’équipe {n},\n\nDar (dar.ink) gère une agence immobilière en un seul endroit : biens, clients, visites, offres, campagnes e-mail et votre propre site, en français, anglais ou arabe, pour tout pays.\n\nOuvrez une agence de démonstration complète, sans inscription :\nhttps://dar.ink/app/?example=1\n\nLes formules sont à -50 % en ce moment, et les 2 premiers jours sont gratuits avant tout prélèvement.\n\nOu recevez chaque semaine un modèle gratuit et une idée pour gagner des clients :\n$U\n\nSi cela ne vous concerne pas, répondez « stop » et nous ne vous écrirons plus.\n\nL’équipe Dar\nservice@dar.ink\n"],
           'b' => ['{n} : programmes, lots et acquéreurs sur une seule plateforme (démo sans inscription)', "Bonjour l’équipe {n},\n\nDar (dar.ink) réunit pour un promoteur ses programmes et lots (vendus, réservés, disponibles), ses acquéreurs, ses échéanciers et les agents qui vendent, en français, anglais ou arabe.\n\nOuvrez la démonstration promoteurs, sans inscription :\nhttps://dar.ink/builders/?example=1\n\nLes formules sont à -50 % en ce moment, et les 2 premiers jours sont gratuits avant tout prélèvement.\n\nOu recevez chaque semaine un modèle gratuit et une idée pour gagner des clients :\n$U\n\nSi cela ne vous concerne pas, répondez « stop » et nous ne vous écrirons plus.\n\nL’équipe Dar\nservice@dar.ink\n"]],
  'ar' => ['o' => ['{n}: نظام واحد لعقارات المكتب وعملائه وعروضه — جرّبوه دون تسجيل', "السلام عليكم فريق {n}،\n\n«دار» (dar.ink) منصة لإدارة المكاتب العقارية: العقارات والعملاء والمعاينات والعروض وحملات البريد وموقع المكتب الخاص في مكان واحد، بالعربية والإنجليزية والفرنسية ولأي دولة.\n\nتصفّحوا مكتبًا تجريبيًا كاملًا الآن دون تسجيل:\nhttps://dar.ink/app/?example=1\n\nالباقات عليها خصم 50% الآن، وأول يومين مجانًا قبل أي خصم من البطاقة.\n\nأو اشتركوا مجانًا في نشرة أسبوعية فيها نموذج جاهز وفكرة لجلب العملاء:\n$U\n\nإن لم يناسبكم هذا ردّوا بكلمة «إيقاف» ولن نراسلكم مرة أخرى.\n\nفريق دار\nservice@dar.ink\n"],
           'b' => ['{n}: مبيعات مشاريعكم ووحداتها في نظام واحد — جرّبوه دون تسجيل', "السلام عليكم فريق {n}،\n\n«دار» (dar.ink) منصة تجمع للمطوّر مشاريعه ووحداته (المباعة والمحجوزة والمتاحة) والمشترين وجداول الدفعات والوسطاء الذين يبيعون، بالعربية والإنجليزية والفرنسية.\n\nتصفّحوا النسخة التجريبية للمطورين دون تسجيل:\nhttps://dar.ink/builders/?example=1\n\nالباقات عليها خصم 50% الآن، وأول يومين مجانًا قبل أي خصم من البطاقة.\n\nأو اشتركوا مجانًا في نشرة أسبوعية فيها نموذج جاهز وفكرة لجلب العملاء:\n$U\n\nإن لم يناسبكم هذا ردّوا بكلمة «إيقاف» ولن نراسلكم مرة أخرى.\n\nفريق دار\nservice@dar.ink\n"]],
];
$res = ['day' => gmdate('Y-m-d'), 'ok' => 0, 'none' => 0, 'skip' => 0, 'fail' => 0, 'from' => $st['cursor']]; $last = $st['cursor'] - 1;
foreach ($batch as $i => $l) {
  [$lg, $kd, $n, $d] = explode('|', $l); $last = $i;
  $base = explode('.', preg_replace('/^www\./', '', $d))[0];
  $ems = array_values(array_filter(array_keys($found[$i] ?? []), fn($e) => strpos(explode('@', $e)[1], $base) !== false || in_array(explode('@', $e)[1], ['gmail.com', 'outlook.com', 'hotmail.com', 'yahoo.com'], true)));
  if (!$ems) { $res['none']++; continue; }
  usort($ems, fn($a, $b) => ((($x = array_search(explode('@', $a)[0], $pref, true)) === false ? 99 : $x) <=> (($y = array_search(explode('@', $b)[0], $pref, true)) === false ? 99 : $y)));
  $to = $ems[0]; if (isset($sent[$to]) || isset($prev[$to])) { $res['skip']++; continue; }
  [$s, $b] = $T[$lg][$kd] ?? $T['en']['o'];
  $ok = send_mail($to, str_replace('{n}', $n, $s), str_replace('{n}', $n, $b), 'campaign', 'platform', false, ['reply' => 'service@dar.ink', 'name' => 'Dar | دار']);
  if (!$ok && strpos((string)($GLOBALS['dar_mail_err'] ?? ''), 'held') === 0) { $res['held'] = $GLOBALS['dar_mail_err']; $last = $i - 1; break; }
  if ($ok && ($GLOBALS['dar_mail_via'] ?? '') !== 'smtp') { $sent[$to] = 'plain'; $res['stopped'] = 'mailbox refused'; break; }
  if ($ok) { $res['ok']++; $sent[$to] = gmdate('c'); } else { $res['fail']++; if ($res['fail'] >= 3) break; }
  file_put_contents($keep, json_encode($sent)); if ($res['ok'] >= 40) break; usleep(3000000);
}
file_put_contents($keep, json_encode($sent));
$st['cursor'] = $last + 1; $st['days']++; $st['sent'] += $res['ok']; file_put_contents("$D/state.json", json_encode($st));
$res['to'] = $st['cursor']; $res['total'] = count($L); $res['all_sent'] = $st['sent']; $log($res);
