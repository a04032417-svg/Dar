<?php
// read-only: the public contact e-mails of the listed offices (their own web pages), fetched in parallel; and whether Dar's demo links answer
$L = array_values(array_filter(array_map('trim', file(__DIR__ . '/leads51.txt'))));
$paths = ['', '/contact', '/contact-us', '/en/contact-us', '/ar/contact-us'];
$jobs = []; foreach ($L as $i => $l) { [$cc, $n, $d] = explode('|', $l); foreach ($paths as $p) $jobs[] = [$i, 'https://' . $d . $p]; }
$found = []; $bad = '/\.(png|jpe?g|gif|svg|webp|css|js)$|example\.|sentry|wixpress|domain\.com|email\.com|yourmail|godaddy|@2x/i';
foreach (array_chunk($jobs, 25) as $chunk) {
  $mh = curl_multi_init(); $hs = [];
  foreach ($chunk as $k => [$i, $u]) { $h = curl_init($u); curl_setopt_array($h, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_MAXREDIRS => 4, CURLOPT_TIMEOUT => 12, CURLOPT_CONNECTTIMEOUT => 6, CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124 Safari/537.36', CURLOPT_MAXFILESIZE => 3000000]); curl_multi_add_handle($mh, $h); $hs[$k] = [$h, $i]; }
  do { $st = curl_multi_exec($mh, $run); if ($run) curl_multi_select($mh, 1.0); } while ($run && $st == CURLM_OK);
  foreach ($hs as [$h, $i]) { $b = (string)curl_multi_getcontent($h); curl_multi_remove_handle($mh, $h); curl_close($h);
    $b = html_entity_decode(str_replace(['[at]', '(at)', ' at '], '@', $b));
    if (preg_match_all('/mailto:([A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,})|([A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,})/', $b, $m)) foreach (array_merge($m[1], $m[2]) as $e) { $e = strtolower(trim($e, '.')); if ($e !== '' && !preg_match($bad, $e)) $found[$i][$e] = 1; }
    if (preg_match_all('#wa\.me/(\d{8,15})|api\.whatsapp\.com/send\?phone=(\d{8,15})#', $b, $w)) foreach (array_merge($w[1], $w[2]) as $p) if ($p !== '') $found[$i]['wa:' . $p] = 1;
  }
  curl_multi_close($mh);
}
$out = []; foreach ($L as $i => $l) { [$cc, $n, $d] = explode('|', $l); $out[] = [$cc, $n, $d, array_keys($found[$i] ?? [])]; }
$chk = []; foreach (['https://dar.ink/', 'https://dar.ink/app/?example=1', 'https://dar.ink/builders/?example=1', 'https://dar.ink/partner/'] as $u) { $h = curl_init($u); curl_setopt_array($h, [CURLOPT_NOBODY => 1, CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 10, CURLOPT_FOLLOWLOCATION => 1]); curl_exec($h); $chk[$u] = curl_getinfo($h, CURLINFO_HTTP_CODE); curl_close($h); }
echo json_encode(['leads' => $out, 'links' => $chk], JSON_UNESCAPED_UNICODE);
