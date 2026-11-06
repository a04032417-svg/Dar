<?php
// Dar's WhatsApp agent, the outreach the owner ordered (2026-11-06 04:02): nothing here is in this file — the list of people and the first
// messages are put into the site's own settings by a one-time step (meta «wa_out_plan»), never into this public repository.
//   php vs144.php status                 — is the owner's number linked? how many of the plan are due / sent / answered?
//   php vs144.php plan <base64 json>      — store the plan
//   php vs144.php send                    — send what is due now (one person at a time, 3 minutes apart, only while the number is linked)
//   php vs144.php watch                   — the conversations so far (what each person wrote, what the agent answered)
ini_set('display_errors', '0'); @set_time_limit(0);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$mode = $argv[1] ?? 'status'; $J = fn($x) => json_encode($x, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
$plan = o2a(jdec((string)(meta_get('wa_out_plan') ?? '[]'))); if (!is_array($plan)) $plan = [];
$open = function_exists('wag_platform_open') && wag_platform_open(); $row = function_exists('wag_row') ? wag_row('platform') : null;
if ($mode === 'plan') { $p = json_decode(base64_decode((string)($argv[2] ?? '')), true); if (!is_array($p)) { echo "bad plan\n"; exit(1); } meta_set('wa_out_plan', jenc($p)); echo "plan stored: " . count($p) . "\n"; exit; }
if ($mode === 'status') { echo $J(['linked' => $open, 'status' => $row['status'] ?? null, 'phone' => $row ? substr((string)$row['phone'], 0, 5) . '…' : null, 'agent_on' => function_exists('wa_agent_all') && wa_agent_all(), 'plan' => count($plan), 'sent' => count(array_filter($plan, fn($x) => !empty($x['sent']))), 'now_riyadh' => (new DateTime('now', new DateTimeZone('Asia/Riyadh')))->format('Y-m-d H:i')]), "\n"; exit; }
if ($mode === 'send') {
    if (!$open) { echo "not linked: nothing sent\n"; exit; }
    $now = time(); $did = 0; $last = (int)(meta_get('wa_out_last') ?? 0);
    foreach ($plan as $i => &$p) {
        if (!empty($p['sent']) || (int)$p['at'] > $now) continue;
        if ($now - $last < 170) { echo "waiting (3 min between people)\n"; break; }
        $r = chan_send('whatsapp', (string)$p['num'], (string)$p['text'], '', ['country' => '', 'real' => true]);
        $p['sent'] = now_ms(); $p['result'] = (string)($r['status'] ?? '?') . ' ' . (string)($r['error'] ?? '');
        if (($r['status'] ?? '') === 'sent') { wa_agent_note((string)$p['num'], 'assistant', (string)$p['text'], 'owner_assistant'); if (function_exists('wa_agent_goal')) wa_agent_goal((string)$p['num'], (string)$p['goal']); meta_set('wa_out_last', (string)$now); $last = $now; }
        $did++; echo $p['name'], ': ', $p['result'], "\n"; break; // (one per run: the cron runs every minute)
    }
    unset($p); meta_set('wa_out_plan', jenc($plan)); if (!$did) echo "nothing due\n"; exit;
}
if ($mode === 'watch') {
    $out = [];
    foreach ($plan as $p) { $th = wa_agent_thread((string)$p['num']); $m = []; foreach (array_slice(is_array($th['m'] ?? null) ? $th['m'] : [], -12) as $x) $m[] = (($x['r'] ?? '') === 'user' ? '👤 ' : '🤖 ') . mb_substr((string)($x['t'] ?? ''), 0, 400);
        $out[] = ['name' => $p['name'], 'sent' => !empty($p['sent']) ? gmdate('H:i', intdiv((int)$p['sent'], 1000)) . 'Z ' . ($p['result'] ?? '') : 'not yet', 'replied' => (bool)array_filter(is_array($th['m'] ?? null) ? $th['m'] : [], fn($x) => ($x['r'] ?? '') === 'user'), 'profile' => $th['profile'] ?? null, 'quiet' => function_exists('waa_quiet') && waa_quiet((string)$p['num']), 'chat' => $m]; }
    echo $J($out), "\n"; exit;
}
