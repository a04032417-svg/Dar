<?php
function req_get_raw(string $u): ?string { $c = curl_init($u); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 20]); $b = curl_exec($c); $ok = curl_getinfo($c, CURLINFO_HTTP_CODE) === 200; curl_close($c); return $ok && is_string($b) ? $b : null; }
// The outreach the owner ordered (2026-11-06 04:02 «نيابة عني اعطي المساعد امر بأن يتحدث اليهم بدء من الساعة 9 صباح وراقب تصرفاته ونتائجه»).
// The list is not in this public repository: p.enc holds it encrypted, its key is on the cron's command line only.
//   php vs145.php send <key>   — every minute: one person whose time came, 3 minutes after the last, between 9:00 and 21:00 Riyadh, never
//                                during Friday prayer; a number not on WhatsApp is skipped; nothing to a person who asked to be left alone
//   php vs145.php watch        — what each person wrote and what the agent answered
ini_set('display_errors', '0'); @set_time_limit(120);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$mode = $argv[1] ?? 'watch'; $J = fn($x) => json_encode($x, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$plan = o2a(jdec((string)(meta_get('wa_out_plan') ?? ''))); if (!is_array($plan)) $plan = [];
if (!$plan && $mode === 'send' && ($k = (string)($argv[2] ?? '')) !== '') {
    $enc = is_file(__DIR__ . '/p.enc') ? (string)file_get_contents(__DIR__ . '/p.enc') : (string)(req_get_raw('https://raw.githubusercontent.com/a04032417-svg/Dar/' . (string)($argv[3] ?? 'main') . '/p.enc') ?? ''); if ($enc !== '') @file_put_contents(__DIR__ . '/p.enc', $enc);
    $raw = base64_decode($enc); $iv = substr($raw, 0, 16);
    $pt = openssl_decrypt(substr($raw, 16), 'aes-256-cbc', base64_decode($k), OPENSSL_RAW_DATA, $iv);
    $plan = $pt !== false ? (json_decode($pt, true) ?: []) : []; if ($plan) meta_set('wa_out_plan', jenc($plan)); echo 'plan loaded: ' . count($plan) . "\n";
}
if ($mode === 'send' && ($k = (string)($argv[2] ?? '')) !== '' && ($cm = (string)($argv[3] ?? '')) !== '' && (string)(meta_get('wa_out_p2') ?? '') !== $cm) {
    $e2 = (string)(req_get_raw('https://raw.githubusercontent.com/a04032417-svg/Dar/' . $cm . '/p2.enc') ?? ''); $n2 = 0;
    if ($e2 !== '') { $r2 = base64_decode($e2); $t2 = openssl_decrypt(substr($r2, 16), 'aes-256-cbc', base64_decode($k), OPENSSL_RAW_DATA, substr($r2, 0, 16)); $l2 = $t2 !== false ? (json_decode($t2, true) ?: []) : [];
        $have = array_map(fn($x) => (string)$x['num'], $plan); foreach (array_reverse($l2) as $x) if (is_array($x) && !in_array((string)($x['num'] ?? ''), $have, true)) { array_unshift($plan, $x); $n2++; }
        if ($n2) meta_set('wa_out_plan', jenc($plan)); }
    meta_set('wa_out_p2', $cm); echo 'added: ' . $n2 . "\n"; // (the owner's later numbers go first)
}
$riy = new DateTime('now', new DateTimeZone('Asia/Riyadh')); $h = (int)$riy->format('G') * 60 + (int)$riy->format('i');
$open = function_exists('wag_platform_open') && wag_platform_open();
if ($mode === 'send') {
    $sent = count(array_filter($plan, fn($x) => !empty($x['sent']))); echo $riy->format('H:i') . " Riyadh · linked " . ($open ? 'yes' : 'NO') . " · sent $sent/" . count($plan) . "\n";
    if (!$open) exit; $closed = '';
    if ($h < 9 * 60 || $h >= 21 * 60) $closed = 'outside 9:00–21:00';
    elseif ($riy->format('N') === '5' && $h >= 11 * 60 + 15 && $h < 13 * 60 + 15) $closed = 'Friday prayer: waiting';
    if ($closed !== '' && !array_filter($plan, fn($x) => empty($x['sent']) && !empty($x['anytime']) && (int)$x['at'] <= time())) { echo $closed . "\n"; exit; } // (a number the owner asked to be written to now is not held to the hours)
    $last = (int)(meta_get('wa_out_last') ?? 0); if (time() - $last < 170) { echo "waiting (3 minutes between people)\n"; exit; }
    foreach ($plan as $i => &$p) {
        if (!empty($p['sent']) || (int)$p['at'] > time() || ($closed !== '' && empty($p['anytime']))) continue;
        $num = (string)$p['num'];
        $th0 = wa_agent_thread($num); $mine = array_filter(is_array($th0['m'] ?? null) ? $th0['m'] : [], fn($x) => ($x['r'] ?? '') === 'user' && (int)($x['at'] ?? 0) > now_ms() - 7 * 86400000);
        if ($mine) { $p['sent'] = now_ms(); $p['result'] = 'skipped: already talking with Dar'; if (function_exists('wa_agent_goal')) wa_agent_goal($num, (string)$p['goal']); echo $p['name'] . ": already talking — no opener, the goal is set\n"; break; } // (an opener in the middle of his own conversation reads like a machine)
        if (function_exists('waa_quiet') && waa_quiet($num)) { $p['sent'] = now_ms(); $p['result'] = 'skipped: asked to be left alone'; break; }
        $chk = wag_call('POST', '/v1/sessions/platform/check', ['numbers' => [$num]], 20); $ex = $chk['json']['results'][0]['exists'] ?? null;
        $p['check'] = $ex === null ? 'unknown' : ($ex ? 'on WhatsApp' : 'not on WhatsApp');
        if ($ex === false) { $p['sent'] = now_ms(); $p['result'] = 'skipped: not on WhatsApp'; echo $p['name'] . ": not on WhatsApp\n"; break; }
        if (function_exists('waa_typing')) { waa_typing($num); usleep(min(6000, 1200 + 22 * mb_strlen((string)$p['text'])) * 1000); }
        $r = function_exists('wag_send') ? wag_send('platform', $num, (string)$p['text']) : chan_send('whatsapp', $num, (string)$p['text'], '', ['country' => '', 'real' => true]); // (through the linked number itself: the site's channel setting would send it to Meta's API, which has no token)
        $res = trim((string)($r['status'] ?? '?') . ' ' . (string)($r['error'] ?? '')); meta_set('wa_out_last', (string)time());
        if (($r['status'] ?? '') !== 'sent') { $p['tries'] = (int)($p['tries'] ?? 0) + 1; $p['result'] = 'try ' . $p['tries'] . ': ' . $res; if ($p['tries'] >= 3) $p['sent'] = now_ms(); echo $p['name'] . ': ' . $p['result'] . "\n"; break; } // (a failed send is tried again 3 minutes later, three times at most)
        $p['sent'] = now_ms(); $p['result'] = $res;
        if (($r['status'] ?? '') === 'sent') { wa_agent_note($num, 'assistant', (string)$p['text'], 'owner_assistant'); if (function_exists('wa_agent_goal')) wa_agent_goal($num, (string)$p['goal']); meta_set('wa_out_last', (string)time()); }
        echo $p['name'] . ': ' . $p['result'] . "\n"; break; // (one person a minute at most)
    }
    unset($p); meta_set('wa_out_plan', jenc($plan)); exit;
}
$out = [];
foreach ($plan as $p) {
    $th = wa_agent_thread((string)$p['num']); $msgs = is_array($th['m'] ?? null) ? $th['m'] : []; $chat = [];
    foreach (array_slice($msgs, -14) as $x) $chat[] = gmdate('H:i', intdiv((int)($x['at'] ?? 0), 1000)) . 'Z ' . ((($x['r'] ?? '') === 'user') ? 'هو: ' : 'دار: ') . mb_substr((string)($x['t'] ?? ''), 0, 500);
    $out[] = ['name' => $p['name'], 'sent' => !empty($p['sent']) ? gmdate('H:i', intdiv((int)$p['sent'], 1000)) . 'Z ' . ($p['result'] ?? '') : 'not yet', 'replies' => count(array_filter($msgs, fn($x) => ($x['r'] ?? '') === 'user')), 'stage' => $th['profile']['stage'] ?? null, 'quiet' => function_exists('waa_quiet') && waa_quiet((string)$p['num']), 'chat' => $chat];
}
echo $J(['at' => $riy->format('H:i') . ' Riyadh', 'linked' => $open, 'people' => $out]), "\n";
