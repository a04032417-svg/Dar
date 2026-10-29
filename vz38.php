<?php
// Dar — read-only look at the shared mind (dar-brain.php) on the live site: the settings it reads right now (free days per plan, prices, the year's switch, the marketers' programme),
// whether its check catches a wrong figure, how many standing rules it holds, and what the platform's own doctor holds open. Nothing is changed; no person's data is printed.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php'; require_once $R . '/dar-doctor.php';
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c'), 'brain_loaded' => function_exists('brain_truth')];
try {
    if (function_exists('brain_truth')) {
        $t = brain_truth(true);
        $o['yearly'] = $t['yearly']; $o['days_all'] = $t['days'];
        foreach ($t['plans'] as $aud => $rows) foreach ($rows as $p) $o['plans'][$aud][] = [$p['code'], $p['price'], $p['now'], $p['trial_m'], $p['trial_y']];
        $o['partner'] = $t['partner'];
        $o['facts_office'] = brain_facts_text('office');
        $o['facts_partner'] = brain_facts_text('partner');
        $lie = ['فترة التجربة المجانية 14 يومًا لكل مكتب.', 'عمولتك 35% عن كل اشتراك.', 'الحد الأدنى للسحب 20 دولار.', 'الباقة السنوية عليها خصم شهرين مجانًا.', 'التجربة المجانية للمكاتب يومين.'];
        foreach ($lie as $s) { $v = brain_claims($s, ['scope' => 'platform', 'kind' => 'office']); $o['check'][] = [$s, array_map(fn($x) => $x['type'], $v), brain_fix($s, $v)]; }
        if (function_exists('mind_table')) mind_table(); $o['rules_n'] = (int)(one("SELECT COUNT(*) n FROM mind_mem WHERE ns = 'platform' AND scope = 'platform' AND kind = 'rule'")['n'] ?? 0);
        $o['agent_threads_n'] = (int)(one("SELECT COUNT(*) n FROM meta WHERE k LIKE 'wa_agent:%'")['n'] ?? 0);
    }
} catch (Throwable $e) { $o['brain_err'] = substr($e->getMessage(), 0, 200); }
try { $o['counts'] = doc_counts(); $rows = [];
    foreach (q("SELECT code, subject, layer, level, status, seen, recur, tries, last_at, first_at FROM doc_inc WHERE status IN ('open','waiting','stuck','fixing') ORDER BY last_at DESC LIMIT 40")->fetchAll() as $r)
        $rows[] = [$r['code'], substr((string)$r['subject'], 0, 40), $r['layer'], $r['level'], $r['status'], (int)$r['seen'], (int)$r['recur'], (int)$r['tries'], gmdate('m-d H:i', (int)($r['last_at'] / 1000))];
    $o['open'] = $rows;
} catch (Throwable $e) { $o['doc_err'] = substr($e->getMessage(), 0, 200); }
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
