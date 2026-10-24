<?php
// Dar — after installing 3.33.24 (read only, nothing sent): the version, the assistant's addresses and their check, the mark-up reader,
// the accounts found by their owner's e-mail, the property pictures it can send, and the owner's WhatsApp path for a picture
$R = '/home/u791571885/domains/dar.ink/public_html'; require $R . '/dar-sync.php';
$u = rtrim(site_url(), '/'); $L = talk_links();
$acc = talk_lookup([], [], ['do' => 'accounts', 'q' => 'swetkw5@gmail.com']);
$ph = talk_lookup([], [], ['do' => 'photos']);
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c'), 'site' => $u,
  'fns' => array_map('function_exists', ['talk_links' => 'talk_links', 'talk_link_ok' => 'talk_link_ok', 'talk_clear' => 'talk_clear', 'ai_json_of' => 'ai_json_of', 'ai_tool_markup' => 'ai_tool_markup', 'eng_op_words' => 'eng_op_words', 'eng_more_ok' => 'eng_more_ok', 'acct_wa_media' => 'acct_wa_media', 'conn_media_of' => 'conn_media_of']),
  'links' => ['engineer' => $L['the engineer\'s tasks page'] ?? null, 'example_office' => array_values(array_filter($L, fn($v) => str_contains($v, '/app/?example=1')))[0] ?? null],
  'link_ok' => ['engineer' => talk_link_ok($u . '/developer/#/d_engineer'), 'example' => talk_link_ok($u . '/app/?example=1#/today'), 'invented_sub' => talk_link_ok('https://trial-office-1.dar.ink'), 'invented_path' => talk_link_ok($u . '/trial-office-1')],
  'markup' => [plain_reply('أبحث <actions>[{"do":"accounts","q":"x"}]</actions>'), ai_json_of('أفحص <actions>[{"do":"health"}]</actions>')],
  'eng_words' => eng_op_words('subs.create', ['app' => 'app', 'name' => 'مثال'], 'ar'),
  'client_by_email' => ['found' => $acc['accounts'] ?? null, 'has_phone' => isset($acc['rows'][0]['owner']['phone'])],
  'photos' => count($ph['photos'] ?? []), 'wa_platform_open' => function_exists('wag_platform_open') ? wag_platform_open() : null,
  'markup_live' => (function () { $x = 'an' . 'tml'; $t = "ابحث.\n<$x:actions>\n<$x:invoke name=\"find\"><$x:parameter name=\"q\">x@y.z</$x:parameter></$x:invoke>\n</$x:actions>"; return [plain_reply($t), ai_tool_markup($t)[1]]; })(),
  'quick_hedge_ms' => defined('AI_QUICK_HEDGE_MS') ? AI_QUICK_HEDGE_MS : null, 'policy' => assist_policy()['confirm'], 'ai_code_by_itself' => function_exists('doc_may') ? doc_may('ai_code') : null, 'eng_tools' => defined('ENG_TOOLS') ? ENG_TOOLS : null,
  'agent_never_muted' => !str_contains((string)@file_get_contents($R . '/dar-connect.php'), "if ((int)strtotime((string)($" . "cv['agent_off_until']"), 'idem' => function_exists('idem_begin') && function_exists('dar_after_fault') && function_exists('doc_quick')];
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
