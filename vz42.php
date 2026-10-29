<?php
// Dar — read-only look at the shared mind (dar-brain.php) on the live site (v3.33.38: also the language and name checks): the settings it reads right now (free days per plan, prices, the year's switch, the marketers' programme),
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
        $o['lang'] = function_exists('brain_lang_wrong') ? [
            'hi_answered_in_arabic_is_wrong' => brain_lang_wrong('وعليكم السلام ورحمة الله، أهلاً وسهلاً بك. كيف يمكنني مساعدتك اليوم؟', 'Hi'),
            'hi_answered_in_english_is_ok' => !brain_lang_wrong('Hello! How can I help you today?', 'Hi'),
            'arabic_answered_in_arabic_is_ok' => !brain_lang_wrong('أهلاً بك، كيف أخدمك؟', 'مرحبا'),
            'name_alone_judges_nothing' => !brain_lang_wrong('أهلاً بك، تفضل', 'Ahmed') && !brain_lang_wrong('Welcome, how can I help?', 'Ahmed'),
            'brand_in_english' => brain_brand_fix('Hello! Try دار today: https://dar.ink'),
            'brand_in_arabic' => brain_brand_fix('أهلاً بك في Dar، منصة العقار'),
            'apply_all_words' => brain_applyall_words('طبق هذا على جميع وكلاء الذكاء الاصطناعي سواء للمطور او المشتركين او الشركاء'),
            'partner_page_in_a_strangers_facts' => (bool)preg_match('~/partner~', brain_facts_text('prospect')),
        ] : 'missing';
        $bad = 'وعليكم السلام ورحمة الله، أهلاً وسهلاً بك. جرب الآن الواجهة التجريبية لمطوري العقارات: https://dar.ink/builders/?example=1#/b_dashboard';
        $den = 'ميزة «رادار السوق» غير متوفرة حالياً على منصة دار.';
        $o['screens'] = function_exists('brain_greeting_kind') ? [
            'hala_kind' => brain_greeting_kind('هلا'),
            'hala_bad_reply_caught' => array_map(fn($x) => $x['type'], brain_claims($bad, ['scope' => 'platform', 'kind' => '', 'said' => 'هلا'])),
            'radar_denial_caught' => array_map(fn($x) => $x['type'], brain_claims($den, ['scope' => 'platform', 'kind' => '', 'said' => 'ماهو رادار السوق'])),
            'radar_hits' => array_map(fn($h) => [$h['s']['id'], $h['s']['st']], brain_feature_hits('ماهو رادار السوق', 2)),
            'catalog_services' => ['app' => count(brain_catalog('app')), 'builders' => count(brain_catalog('builders')), 'providers' => count(brain_catalog('providers')), 'partner' => count(brain_catalog('partner')), 'console' => count(brain_catalog('console'))],
            'features_chars' => ['prospect_en' => strlen(brain_features_text('', '', 1)), 'office_ar' => strlen(brain_features_text('office', 'رادار السوق', 0))],
            'soon_marked' => substr_count(brain_features_text('', '', 1), 'COMING SOON'),
            'recent_people_function' => function_exists('wa_recent') && function_exists('talk_recent_people'),
            'in_app_wired' => function_exists('brain_wire') && function_exists('brain_wrap'),
        ] : 'missing';
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
