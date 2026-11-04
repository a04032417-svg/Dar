<?php
// After 3.33.54: set the platform's setup-call booking page (Calendly, the owner linked it 2026-11-04) where the company details keep it,
// then check that the free tools, the offices' landing and the sitemap carry what they should (no personal data printed)
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$B = 'https://calendly.com/service-dar/dar-setup-15-min'; $o = ['ver' => SYNC_VERSION];
$o['book_before'] = (string)(platform_rec()->landing->company->book ?? '');
if ($o['book_before'] !== $B) tx(function () use ($B): void { $P = platform_rec(); if (!isset($P->landing) || !is_object($P->landing)) $P->landing = new stdClass(); if (!isset($P->landing->company) || !is_object($P->landing->company)) $P->landing->company = new stdClass(); $P->landing->company->book = $B; kv_put('platform', 'platform', jenc($P), 'calendly'); });
$o['book_after'] = (string)(platform_rec()->landing->company->book ?? '');
$c = curl_init('https://dar.ink/sitemap.xml'); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25]); $sm = (string)curl_exec($c); curl_close($c);
$o['sitemap_free'] = preg_match_all('~/free/[a-z]*/?</loc>~', $sm);
$c = curl_init($B); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_FOLLOWLOCATION => true, CURLOPT_NOBODY => true]); curl_exec($c); $o['calendly_http'] = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c);
$o['free_pages'] = (int)(one("SELECT COUNT(*) n FROM meta WHERE k LIKE 'gl:%'")['n'] ?? 0);
$c = curl_init('https://dar.ink/free/l/zzzzzzzz'); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25]); curl_exec($c); $o['free_page_route'] = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); // (404 from Dar's own page, not the host's)
$o['health'] = jdec((string)(meta_get('health') ?? ''))->reasons ?? null;
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
