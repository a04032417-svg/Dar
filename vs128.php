<?php
// Why 4 checkouts in a week ended with no payment: the payment provider's last error, the checkout events, the platform's currency and
// prices as a visitor sees them, the trial e-mails sent
ini_set('display_errors', '0'); @set_time_limit(120);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION]; $P = platform_rec(); $d = fn($ms) => $ms ? gmdate('m-d H:i', intdiv((int)$ms, 1000)) : '';
$o['cur'] = ['base' => (string)($P->base_currency ?? ''), 'usd_rate' => $P->base_usd_rate ?? null];
$o['whop_last_err'] = jdec((string)(meta_get('whop_last_err') ?? 'null'));
try { $w = whop_cfg(); $o['whop'] = ['on' => $w['on'], 'ready' => whop_ready($w), 'env' => $w['env'] ?? '', 'keys' => array_values(array_diff(array_keys($w), ['key', 'secret']))]; } catch (Throwable $e) { $o['whop'] = 'x:' . $e->getMessage(); }
$o['checkouts'] = array_map(fn($r) => ['at' => $d($r['at']), 'path' => $r['path'], 'app' => $r['app'], 't' => substr(md5((string)$r['tenant']), 0, 6), 'cc' => $r['cc'], 'dev' => $r['dev'], 'bot' => $r['bot'], 'src' => $r['src'], 'via' => $r['via']], q("SELECT * FROM la_events WHERE kind IN ('checkout','paid') ORDER BY at DESC LIMIT 15")->fetchAll());
$o['signups'] = array_map(fn($r) => ['at' => $d($r['at']), 'kind' => $r['kind'], 'path' => $r['path'], 'app' => $r['app'], 'cc' => $r['cc'], 'src' => $r['src'], 'bot' => $r['bot']], q("SELECT * FROM la_events WHERE kind IN ('signup_start','signup_submit','signup') AND at > ? ORDER BY at DESC LIMIT 40", [(time() - 7 * 86400) * 1000])->fetchAll());
try { $o['mail'] = q("SELECT kind, COUNT(*) n, MAX(at) m FROM mail_log WHERE at > ? GROUP BY kind ORDER BY n DESC LIMIT 20", [(time() - 14 * 86400) * 1000])->fetchAll(); } catch (Throwable $e) { $o['mail'] = 'x:' . $e->getMessage(); }
try { $o['mail_cols'] = array_column(q("PRAGMA table_info(mail_log)")->fetchAll(), 'name'); } catch (Throwable $e) {}
$o['plans'] = []; foreach (['office', 'builder'] as $a) foreach (grow_plans($a) as $p) $o['plans'][] = array_intersect_key($p, array_flip(['code', 'base_price', 'price', 'currency', 'trial_days', 'card_first', 'checkout_first', 'whop_plan', 'whop', 'year_price', 'cycle']));
$o['plan_keys'] = array_keys((array)(grow_plans('office')[0] ?? []));
$log = @file_get_contents(data_dir() . '/server-errors.log'); $o['err_whop'] = $log ? array_slice(array_values(array_filter(explode("\n", $log), fn($l) => stripos($l, 'whop') !== false || stripos($l, 'checkout') !== false || stripos($l, 'pay') !== false)), -8) : [];
echo json_encode($o, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
