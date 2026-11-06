<?php
// The 11th live conversation (2026-11-06, the owner: «ردوده غير جيده») replayed on the live server with the platform's real AI, after 3.33.94.
// NOTHING IS SENT — wa_dar_agent only writes its answer. Test numbers of nobody (…0004xx); every trace of them is removed at the end.
ini_set('display_errors', '0'); @set_time_limit(0); @ini_set('memory_limit', '512M');
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$open = 'هلا والله 🌟 معك «دار»، منصة تخدم أهل العقار: ترتّب لك العملاء والعقارات والمواعيد في مكان واحد، وموظف ذكي يرد على عملائك في الواتساب حتى وأنت نايم. تشتغل في العقار؟ مكتب، تسويق، ولا تطوير؟';
$goal = 'رقم أعطاه مالك «دار» للتسويق، ولا نعرف صاحبه. اكتشف بلطف ما عمله واعرض ما يناسبه وحده.';
$P = [
    'نفس المحادثة' => ['966500000401', true, ['العقار', 'مافهمت', 'غالي وكلامك غير مفهوم وجاي تسأل على خصوصيات قبل ما افهم', 'رسائلك طويلة ولا لي خلق اقرأ', 'طيب وش الفايدة لي؟', '😂', '😂', '😂', 'من هو']],
    'بكم من أول رسالة' => ['966500000402', false, ['بكم', 'بالريال السعودي', 'ممكن موقعكم', 'اقصد اللوكيشن']],
    'مكتب في جدة' => ['966500000403', false, ['عندي مكتب عقاري بجدة، وش تقدمون؟', 'كم السعر؟', 'غالي']],
];
$GLOBALS['waa_no_retry'] = true; $out = ['ver' => SYNC_VERSION, 'at' => gmdate('c')];
foreach ($P as $who => [$num, $seed, $turns]) {
    if ($seed) { wa_agent_note($num, 'assistant', $open, 'owner_assistant'); wa_agent_goal($num, $goal); }
    $conv = [];
    foreach ($turns as $t) {
        $t0 = microtime(true);
        try { $r = wa_dar_agent($num, $t, ['type' => 'text']); $rep = is_array($r) ? (string)($r['reply'] ?? '') : '(لا رد)'; } catch (Throwable $e) { $rep = 'ERR ' . $e->getMessage(); }
        $by = one("SELECT provider, model FROM lrn_calls WHERE fn = 'wa.agent' ORDER BY id DESC LIMIT 1") ?: [];
        $conv[] = ['in' => $t, 'out' => $rep, 'n' => mb_strlen($rep), 's' => round(microtime(true) - $t0, 1), 'by' => trim((string)($by['provider'] ?? '') . ' ' . (string)($by['model'] ?? ''))];
    }
    $th = wa_agent_thread($num); $out['people'][$who] = ['turns' => $conv, 'notes' => $th['profile'] ?? null];
}
foreach ($P as [$num]) { q('DELETE FROM meta WHERE k IN (?, ?, ?, ?)', ['wa_agent:' . $num, 'wa_agent_q:' . $num, 'wa_agent_h:' . $num, 'wa_agent_rt:' . $num]); foreach (['wa_agent', 'waa_site', 'waa_grade', 'waa_listing', 'waa_hear', 'waa_voice', 'waa_web'] as $b) q('DELETE FROM hits WHERE k = ?', [hash('sha256', $b . ':' . $num . '|*')]); }
$l = o2a(jdec((string)(meta_get('wa_recent') ?? ''))); if (is_array($l)) meta_set('wa_recent', jenc(array_values(array_filter($l, fn($x) => !in_array((string)($x['num'] ?? ''), array_column($P, 0), true)))));
$out['cleaned'] = (int)(one("SELECT COUNT(*) n FROM meta WHERE k LIKE 'wa_agent%:9665000004%'")['n'] ?? -1);
echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
