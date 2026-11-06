<?php
// After 3.33.104 (read-only, nothing sent): one internet look-up of the WhatsApp agent, live; and the daily search's state with its last messages
// (businesses' public names only, no number).
ini_set('display_errors', '0'); @set_time_limit(0); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'at' => gmdate('c')]; $t0 = microtime(true);
$o['web'] = ['say' => mb_substr(waa_web('What is the average monthly rent of a one-bedroom apartment in Dubai Marina now?', 'en'), 0, 400), 's' => round(microtime(true) - $t0, 1)];
$o['web_call'] = one("SELECT provider, model, ok, code, ms FROM lrn_calls WHERE fn = 'wa.agent.web' ORDER BY id DESC LIMIT 1");
$o['pr'] = pr_summary(); $o['pr_state'] = pr_state();
$o['pr_sent'] = array_map(fn($r) => gmdate('d H:i', intdiv((int)$r['sent_at'], 1000)) . ' ' . $r['cc'] . ' ' . $r['name'] . ' (' . $r['lang'] . ', ' . $r['tz'] . '): ' . mb_substr((string)$r['msg'], 0, 330), q("SELECT * FROM pr_leads WHERE st = 'sent' ORDER BY sent_at DESC LIMIT 5")->fetchAll());
$o['pr_ready'] = array_map(fn($r) => $r['cc'] . ' ' . $r['name'] . ' fit' . $r['fit'] . ' ' . $r['hsrc'], q("SELECT * FROM pr_leads WHERE st = 'ready' ORDER BY fit DESC LIMIT 12")->fetchAll());
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR), "\n";
