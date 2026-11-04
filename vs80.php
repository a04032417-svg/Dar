<?php
// After 3.33.55: (1) the booking link (kept), (2) the hub writes toward what brokers really search in Saudi Arabia (Ubersuggest,
// 2026-11-04) instead of topics it imagined — real queries as its next topics, ranked by their monthly searches — and a few more
// pages a day; (3) checks of the new pages. Counts only, no personal data.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php'; grow_db();
$B = 'https://calendly.com/service-dar/dar-setup-15-min'; $o = ['ver' => SYNC_VERSION];
tx(function () use ($B): void { $P = platform_rec(); foreach (['landing', 'grow'] as $k) if (!isset($P->$k) || !is_object($P->$k)) $P->$k = new stdClass(); if (!isset($P->landing->company) || !is_object($P->landing->company)) $P->landing->company = new stdClass(); $P->landing->company->book = $B; $P->grow->ai_daily = 8; kv_put('platform', 'platform', jenc($P), 'growth'); });
$T = [ // [query, monthly searches in Saudi Arabia (Ubersuggest), audience, kind]
  ['اسم مكتب عقاري مميز: أفكار وقواعد لاختيار الاسم', 600, 'office', 'guide'], ['تصميم اعلان عقاري يجذب المشترين: العناصر والأخطاء', 480, 'office', 'guide'],
  ['شعار مكتب عقاري: كيف تختاره وما الذي يجب أن يقوله', 300, 'office', 'guide'], ['من يدفع عمولة الوسيط العقاري: البائع أم المشتري؟', 300, 'office', 'question'],
  ['دراسة جدوى مكتب عقاري: التكاليف والإيرادات وكيف تحسبها', 400, 'office', 'guide'], ['فتح مكتب عقاري إلكتروني: ما تحتاجه للبدء', 350, 'office', 'guide'],
  ['تسويق عقاري بالعمولة: كيف تبدأ وتكسب من كل صفقة', 500, 'partner', 'guide'], ['تسويق عقاري عن بعد: الأدوات وطريقة العمل', 300, 'partner', 'guide'],
  ['إدارة الأملاك للمكاتب العقارية: تحصيل الإيجارات وتجديد العقود', 400, 'office', 'guide'], ['كيف يجد المكتب العقاري عملاء جدد كل أسبوع', 450, 'office', 'guide'],
];
$n = 0; $now = now_ms();
foreach ($T as [$t, $d, $a, $k]) { if (one('SELECT 1 x FROM grow_topics WHERE title = ?', [$t])) continue; q('INSERT INTO grow_topics (id, lang, kind, aud, title, intent, src, demand, created, upd) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', ['tp' . bin2hex(random_bytes(5)), 'ar', $k, $a, $t, $t, 'seo', $d, $now, $now]); $n++; }
$o['topics_added'] = $n; $o['topics_open'] = (int)(one("SELECT COUNT(*) n FROM grow_topics WHERE status = 'open'")['n'] ?? 0); $o['ai_daily'] = grow_cfg()['ai_daily'];
$o['book'] = (string)(platform_rec()->landing->company->book ?? '');
$get = function (string $u): array { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_HEADER => true]); $r = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s, $r]; };
foreach (['/free/lease/', '/free/fal/', '/free/commission/?embed=1'] as $p) { [$s, $r] = $get('https://dar.ink' . $p); $o['page'][$p] = [$s, preg_match('~<title>([^<]*)</title>~u', $r, $m) ? $m[1] : '', stripos($r, 'x-frame-options') !== false ? 'xfo' : 'framable']; }
[$s, $r] = $get('https://dar.ink/sitemap.xml'); $o['sitemap_free'] = preg_match_all('~/free/[a-z]*/?</loc>~', $r);
$c = curl_init('https://api.indexnow.org/indexnow'); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_POST => true, CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'], CURLOPT_POSTFIELDS => json_encode(['host' => 'dar.ink', 'key' => 'f0862deb61759210d196dd529c2da31e', 'keyLocation' => 'https://dar.ink/f0862deb61759210d196dd529c2da31e.txt', 'urlList' => ['https://dar.ink/free/', 'https://dar.ink/free/lease/', 'https://dar.ink/free/fal/', 'https://dar.ink/free/listing/', 'https://dar.ink/llms.txt']])]); curl_exec($c); $o['indexnow'] = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c);
$o['health'] = jdec((string)(meta_get('health') ?? ''))->reasons ?? null;
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
