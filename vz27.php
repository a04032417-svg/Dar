<?php
// Dar — read-only look at the live site after 3.33.34 (2026-10-29): the version, the yearly plans (must stay off), what the platform itself counts as «soon» (and why),
// whether the new tables and duties exist, and the latest server errors. No key, secret, number or name is printed. Nothing is changed (no table is created).
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php'; if (!function_exists('pl_cfg') && is_file($R . '/dar-places.php')) require_once $R . '/dar-places.php';
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
$s = function (string $label, callable $f) use (&$o) { try { $o[$label] = $f(); } catch (Throwable $e) { $o[$label] = 'ERR ' . substr($e->getMessage(), 0, 160); } };
$s('billing', fn() => ['yearly' => bill_cfg()['yearly'] ?? null, 'min_payout' => platform_rec()->min_payout ?? null]);
$s('modules', fn() => ['autopilot_duty' => function_exists('ap_duty'), 'autopilot_op' => function_exists('op_autopilot'), 'sitedev' => function_exists('op_web_dev') && function_exists('sd_db'), 'feat_autopilot' => function_exists('feat_defs') ? isset(feat_defs()['autopilot']) : null, 'claim_guard' => function_exists('talk_unsupported') && function_exists('talk_ground_text'), 'score' => function_exists('ap_score_leads') && function_exists('ap_team_audit') && function_exists('ap_automation_audit') && function_exists('ap_forecast')]);
$s('guard_check', fn() => ['offer_blocked' => count(talk_unsupported('احصل الآن على استشارة مجانية لتطوير مشروعك العقاري. لا تفوت الفرصة!', 'أرسل إلى مرام رسالة تسويقية')) > 0, 'discount_blocked' => count(talk_unsupported('استفد من خصومات حصرية على خدماتنا', '')) > 0, 'plain_passes' => talk_unsupported('الاجتماع غدا الساعة 5 مساء', '') === [], 'year_cut' => talk_ground_text('نقدم الاستشارة منذ إطلاق المنصة في عام 2024.', '')[1] === 1]);
$s('tables', function () { $t = []; foreach (db()->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name IN ('ap_cfg','ap_state','ap_actions','site_dev')")->fetchAll(PDO::FETCH_COLUMN) as $n) $t[$n] = (int)db()->query('SELECT COUNT(*) FROM ' . $n)->fetchColumn(); return $t; });
$s('soon_auto', fn() => ['features' => feat_auto(), 'why' => function_exists('feat_rq_why') ? feat_rq_why() : null]);
$s('services', function () { $P = platform_rec(); $by = []; foreach (acct_catalog($P, 'app') as $svc) { $st = function_exists('feat_svc_status') ? feat_svc_status($svc, $P) : (string)($svc['status'] ?? ''); $by[$st] = ($by[$st] ?? 0) + 1; } return $by; });
$s('cron', fn() => ['at' => meta_get('cron_at') ? gmdate('c', (int)meta_get('cron_at')) : null, 'via' => meta_get('cron_via')]);
$s('errors', function () { $f = data_dir() . '/server-errors.log'; if (!is_file($f)) return 'no log'; $sz = filesize($f); $fh = fopen($f, 'rb'); fseek($fh, max(0, $sz - 60000)); $t = (string)stream_get_contents($fh); $l = array_values(array_filter(explode("\n", $t), fn($x) => preg_match('/autopilot|sitedev|site_dev|duty|web\.dev|sites\.dev|fatal|parse/i', $x))); return array_map(fn($x) => substr($x, 0, 260), array_slice($l, -12)); });

$c = ['user' => ['id' => 'probe-read-only'], 'dev' => false];
$pl = function (string $what, array $p) use ($c) { $t0 = microtime(true); try { $r = op_places($c, $what, $p); } catch (Throwable $e) { return ['ms' => (int)((microtime(true) - $t0) * 1000), 'exception' => substr($e->getMessage(), 0, 120)]; }
    return ['ms' => (int)((microtime(true) - $t0) * 1000), 'failed' => !empty($r['failed']), 'err' => (string)($r['err'] ?? ''), 'cached' => !empty($r['cached']), 'size' => isset($r['counts']) ? count($r['counts']) : (isset($r['items']) ? count($r['items']) : strlen((string)($r['text'] ?? '')))]; };
$s('map_districts', fn() => $pl('osm', ['kind' => 'districts', 'lat' => 27.5114, 'lon' => 41.7208, 'r' => 10000]));
$s('map_nearby', fn() => $pl('osm', ['kind' => 'nearby', 'lat' => 28.3835, 'lon' => 36.5662, 'r' => 1500]));
$s('map_market', fn() => $pl('osm', ['kind' => 'market', 'lat' => 18.2164, 'lon' => 42.5053, 'r' => 4000]));
$s('map_offices', fn() => $pl('osm', ['kind' => 'offices', 'lat' => 26.4207, 'lon' => 50.0888, 'r' => 3000]));
$s('map_geocode', fn() => $pl('geocode', ['q' => 'Muscat', 'cc' => 'OM', 'lang' => 'en']));
$s('map_macro', fn() => $pl('macro', ['cc' => 'OM']));
$s('mirrors_memory', fn() => array_map(fn($e) => [($e['ok'] ?? 0) ? gmdate('H:i:s', (int)$e['ok']) : '-', ($e['bad'] ?? 0) ? gmdate('H:i:s', (int)$e['bad']) : '-', (int)($e['n'] ?? 0)], pl_mirror_state()));
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
