<?php
// After 3.33.102 (the owner, 2026-11-06: «منظومة الواتساب لازالت غبية سواء للمطور او المشتركين او الزوار»): a READ-ONLY look at the last days'
// real WhatsApp conversations — Dar's agent with visitors and offices, the owner's and the offices' own assistants — to find what still goes wrong.
// Nothing is sent or changed. Numbers are masked (their last 3 digits only); messages are cut short. The output stays in the host's cron output.
ini_set('display_errors', '0'); @set_time_limit(0); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$mask = fn(string $s): string => (string)preg_replace_callback('/\+?\d[\d\s-]{6,}\d/', fn($m) => '•••' . substr((string)preg_replace('/\D/', '', $m[0]), -3), $s);
$cut = fn(string $s, int $n = 230): string => $mask(mb_strlen($s) > $n ? mb_substr($s, 0, $n) . '…' : $s);
$since = (time() - 4 * 86400) * 1000; $o = ['v' => SYNC_VERSION, 'at' => gmdate('c')];
// 0. the daily search for offices (3.33.102): where it stands, the last ones judged and sent (businesses' public names, no number)
if (function_exists('pr_summary')) { try { $o['pr'] = pr_summary(); $o['pr_last'] = array_map(fn($r) => $r['st'] . ' ' . $r['cc'] . ' ' . $r['name'] . ' fit' . $r['fit'] . ' ' . $r['hsrc'] . ' ' . mb_substr((string)$r['reason'], 0, 50), q('SELECT st, cc, name, fit, hsrc, reason FROM pr_leads ORDER BY chk DESC LIMIT 14')->fetchAll()); $o['pr_state'] = pr_state(); } catch (Throwable $e) { $o['pr_err'] = $e->getMessage(); } }
// A. Dar's own WhatsApp agent (visitors, offices it wrote to)
$rows = q("SELECT k, v FROM meta WHERE k LIKE 'wa_agent:%'")->fetchAll(); $th = [];
foreach ($rows as $r) { $j = json_decode((string)$r['v'], true); if (!is_array($j)) continue; $m = array_values(array_filter((array)($j['m'] ?? []), fn($x) => (int)($x['at'] ?? 0) >= $since)); if (!$m) continue;
    $last = (int)end($m)['at']; $th[] = [$last, ['n' => '•••' . substr((string)$r['k'], -3), 'who' => isset($j['who']) ? $cut((string)$j['who'], 90) : null, 'goal' => isset($j['goal']['t']) ? $cut((string)$j['goal']['t'], 90) : null, 'st' => $j['profile']['stage'] ?? null,
        'm' => array_map(fn($x) => ($x['r'] === 'user' ? 'U' : 'A' . (!empty($x['by']) ? '(' . $x['by'] . ')' : '')) . ' ' . gmdate('d H:i', intdiv((int)$x['at'], 1000)) . ': ' . $cut((string)($x['t'] ?? '')), array_slice($m, -9))]]; }
usort($th, fn($a, $b) => $b[0] <=> $a[0]); $o['agent'] = array_column(array_slice($th, 0, 18), 1); $o['agent_threads'] = count($th);
// B. the assistants people talk with (the owner's — «developer» —, an office's staff): their WhatsApp and Telegram turns
try { $mt = q('SELECT id, ns, app, surface, updated FROM mind_thread WHERE updated >= ? ORDER BY updated DESC LIMIT 40', [$since])->fetchAll(); $o['mind'] = [];
    foreach ($mt as $t) { $ms = q('SELECT role, text, meta, at FROM mind_msg WHERE thread = ? AND at >= ? ORDER BY id DESC LIMIT 8', [$t['id'], $since])->fetchAll(); if (!$ms) continue;
        $via = ''; foreach ($ms as $x) { $mm = json_decode((string)$x['meta'], true); if (!empty($mm['via'])) { $via = (string)$mm['via']; break; } }
        if (count($o['mind']) >= 12) break;
        $o['mind'][] = ['ns' => $t['ns'] === 'platform' ? 'platform' : 'office', 'app' => $t['app'], 'sf' => $t['surface'], 'via' => $via, 'm' => array_map(fn($x) => ($x['role'] === 'user' ? 'U' : 'A') . ' ' . gmdate('d H:i', intdiv((int)$x['at'], 1000)) . ': ' . $cut((string)$x['text']), array_reverse($ms))]; } }
catch (Throwable $e) { $o['mind_err'] = $e->getMessage(); }
// C. what the AI calls behind them answered with (failures, slowness)
try { $o['calls'] = q("SELECT fn, COUNT(*) n, SUM(CASE WHEN ok = 0 THEN 1 ELSE 0 END) bad, ROUND(AVG(ms)) ms FROM lrn_calls WHERE at >= ? AND (fn LIKE 'wa%' OR fn LIKE 'talk%' OR fn LIKE 'brain%') GROUP BY fn ORDER BY n DESC LIMIT 12", [$since])->fetchAll(); }
catch (Throwable $e) { $o['calls_err'] = substr($e->getMessage(), 0, 120); }
$lines = @file(data_dir() . '/server-errors.log') ?: []; $o['faults'] = array_values(array_map(fn($l) => $cut((string)$l, 170), array_slice(array_filter($lines, fn($l) => preg_match('/wa|talk|agent|brain|pr:/i', $l) && strtotime((string)(json_decode($l, true)['t'] ?? '')) >= time() - 4 * 86400), -8)));
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR), "\n";
