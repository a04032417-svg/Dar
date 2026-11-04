<?php
// After 3.33.51: the free tools are live — check them as a visitor would, let the AI write one listing, and tell Bing/Yandex (IndexNow) about the pages.
$H = 'https://dar.ink'; $K = 'f0862deb61759210d196dd529c2da31e'; $o = [];
$get = function ($u) { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_FOLLOWLOCATION => true]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s, $b]; };
$post = function ($u, $j) { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($j), CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8']]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s, $b]; };
foreach (['/free/', '/free/commission/', '/free/listing/', '/free/contract/', '/' . $K . '.txt'] as $p) { [$s, $b] = $get($H . $p); $o['page'][$p] = [$s, preg_match('~<title>([^<]*)</title>~u', $b, $m) ? $m[1] : substr($b, 0, 40)]; }
[$s, $b] = $get($H . '/sitemap.xml'); $o['sitemap_free'] = substr_count($b, '/free/');
[$s, $b] = $get($H . '/llms.txt'); $o['llms_free'] = substr_count($b, '/free/');
$msgs = [['role' => 'system', 'content' => 'You write property listing descriptions for real-estate brokers. Write in Arabic only. Use ONLY the facts given. 90 to 160 words.'], ['role' => 'user', 'content' => "Property type: Apartment\nFor: sale\nCity: Riyadh\nArea (m²): 180\nBedrooms: 3\nPrice: 950,000"]];
[$s, $b] = $post($H . '/dar-sync.php', ['op' => 'ai.demo', 'payload' => ['purpose' => 'try', 'messages' => $msgs, 'tier' => 'economy', 'max_tokens' => 700], 'app' => 'site', 'lang' => 'ar']);
$j = json_decode($b, true); $o['ai'] = [$s, !empty($j['ok']), mb_substr((string)($j['data']['text'] ?? ($j['error']['code'] ?? '')), 0, 160)];
[$s, $b] = $post('https://api.indexnow.org/indexnow', ['host' => 'dar.ink', 'key' => $K, 'keyLocation' => $H . '/' . $K . '.txt', 'urlList' => [$H . '/', $H . '/free/', $H . '/free/commission/', $H . '/free/listing/', $H . '/free/contract/', $H . '/pricing', $H . '/pricing?lang=en', $H . '/llms.txt']]);
$o['indexnow'] = [$s, substr($b, 0, 120)];
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
