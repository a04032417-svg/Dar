<?php
// After 3.33.103: read-only — the daily search for offices (its state, the last ones judged and written to: businesses' public names only), and why the
// WhatsApp agent's internet look-ups (wa.agent.web) all failed in the last days: the calls' codes and each searching provider's resting state.
ini_set('display_errors', '0'); @set_time_limit(0); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'at' => gmdate('c')];
try { $o['pr'] = pr_summary(); $o['pr_state'] = pr_state();
    $o['pr_last'] = array_map(fn($r) => $r['st'] . ' ' . $r['cc'] . ' ' . $r['city'] . ' · ' . $r['name'] . ' · fit' . $r['fit'] . ' ' . $r['hsrc'] . ' ' . $r['tz'] . ' ' . mb_substr((string)$r['reason'], 0, 70), q('SELECT * FROM pr_leads ORDER BY chk DESC LIMIT 22')->fetchAll());
    $o['pr_sent'] = array_map(fn($r) => gmdate('d H:i', intdiv((int)$r['sent_at'], 1000)) . ' ' . $r['cc'] . ' ' . $r['name'] . ' (' . $r['lang'] . '): ' . mb_substr((string)$r['msg'], 0, 260), q("SELECT * FROM pr_leads WHERE st = 'sent' ORDER BY sent_at DESC LIMIT 6")->fetchAll()); }
catch (Throwable $e) { $o['pr_err'] = $e->getMessage(); }
try { $o['web_calls'] = q("SELECT provider, model, ok, code, ms, tries FROM lrn_calls WHERE fn = 'wa.agent.web' ORDER BY id DESC LIMIT 8")->fetchAll(); } catch (Throwable $e) { $o['web_err'] = $e->getMessage(); }
$o['web_rest'] = json_decode((string)(meta_get('ai_web_rest2') ?? ''), true); $o['ai_web'] = defined('AI_WEB') ? AI_WEB : null;
$o['ready'] = function_exists('ai_ready') && function_exists('ai_cfg') ? ai_ready(ai_cfg()) : null;
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR), "\n";
