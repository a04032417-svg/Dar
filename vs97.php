<?php
// Diagnosis: why e-mail fails, which free tools fail live (and why)
ini_set('display_errors', '1'); error_reporting(E_ALL); @set_time_limit(400);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION]; $H = 'https://dar.ink';
$post = function (string $u, $body, int $t = 60): array { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $t, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE), CURLOPT_HTTPHEADER => ['Content-Type: application/json']]); $b = (string)curl_exec($c); $s = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$s, $b]; };
$m = mail_cfg(); $o['mail'] = ['enabled' => $m['enabled'], 'from_name' => $m['from_name'], 'from_email' => $m['from_email'], 'smtp_host' => $m['smtp_host'], 'smtp_port' => $m['smtp_port'], 'smtp_user_set' => $m['smtp_user'] !== '', 'pass_set' => (string)(vault_get('smtp_password') ?? '') !== '', 'brand' => brand_name()];
$o['mail_log'] = array_map(fn($r) => [gmdate('m-d H:i', intdiv((int)$r['at'], 1000)), $r['kind'], (int)$r['ok'], $r['via'], substr((string)$r['err'], 0, 120)], q('SELECT at, kind, ok, via, err FROM mail_log ORDER BY at DESC LIMIT 14')->fetchAll());
$o['mail_fail_kinds'] = q("SELECT kind, via, COUNT(*) AS n FROM mail_log WHERE ok = 0 AND at > ? GROUP BY kind, via ORDER BY n DESC LIMIT 8", [now_ms() - 7 * 86400000])->fetchAll();
$o['ai_public'] = ai_cfg()['public'] ?? null;
[$s, $b] = $post($H . '/dar-sync.php', ['op' => 'ai.demo', 'payload' => ['purpose' => 'try', 'messages' => [['role' => 'user', 'content' => 'اكتب جملة واحدة عن شقة في الرياض']], 'tier' => 'economy', 'max_tokens' => 200], 'app' => 'site']);
$o['ai_demo'] = [$s, substr($b, 0, 300)];
foreach (['https://www.savills.com/', 'https://sa.aqar.fm/', 'https://www.rafal.com.sa/'] as $u) { $t0 = microtime(true); [$s, $b] = $post($H . '/dar-sync.php', ['op' => 'free.grade', 'payload' => ['url' => $u, 'lang' => 'ar'], 'app' => 'site']); $j = json_decode($b, true); $o['grade'][$u] = [$s, $j['ok'] ?? null, $j['data']['score'] ?? ($j['error']['code'] ?? substr($b, 0, 160)), round(microtime(true) - $t0, 1)]; }
$o['health'] = jdec((string)(meta_get('health') ?? ''))->reasons ?? null;
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
