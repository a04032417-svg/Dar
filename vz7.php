<?php
// Dar 3.33.25 after the install (read only except one visitor question to the site's AI door, to time it)
$R = '/home/u791571885/domains/dar.ink/public_html'; require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
$ui = file_get_contents($R . '/assets/ui.js'); $sm = file_get_contents($R . '/assets/sitemind.js');
$o['preCheckout'] = strpos($ui, 'export function preCheckout') !== false; $o['siteAnswer'] = strpos($sm, 'export function siteAnswer') !== false;
$o['plans'] = []; foreach (bill_platform_rows('plans') as $pl) if (in_array((string)($pl->audience ?? ''), ['builder', 'office'], true)) $o['plans'][] = [(string)$pl->audience, (string)$pl->code, $pl->trial_days ?? null, trial_plan_days((string)$pl->audience, (string)$pl->code, 'month')];
$o['trial_checkout_first_on'] = trial_checkout_first_on(); $o['card_ready'] = trial_card_ready();
$t0 = microtime(true);
try { $r = op_ai_demo(['purpose' => 'site', 'messages' => [['role' => 'system', 'content' => 'Answer in one short Arabic sentence: what is Dar?'], ['role' => 'user', 'content' => 'ما هي دار؟']], 'tier' => 'standard', 'max_tokens' => 200]); $o['ai'] = ['ms' => (int)round((microtime(true) - $t0) * 1000), 'provider' => $r['provider'] ?? '', 'text' => mb_substr((string)($r['text'] ?? ''), 0, 120)]; }
catch (Throwable $e) { $o['ai'] = ['ms' => (int)round((microtime(true) - $t0) * 1000), 'err' => substr($e->getMessage(), 0, 160)]; }
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR), "\n";
