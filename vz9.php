<?php
// Dar — read-only look at the live site (2026-10-28, second): sign-up and checkout errors, plans, payments. Writes nothing.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
$s = function (callable $f) { try { return $f(); } catch (Throwable $e) { return 'ERR ' . substr($e->getMessage(), 0, 200); } };
$o['la_errors'] = $s(fn() => array_map(fn($r) => [gmdate('m-d H:i', intdiv((int)$r['at'], 1000)), $r['kind'], $r['path'], $r['app'], substr((string)$r['tenant'], -6), $r['cc'], $r['dev']], q("SELECT at, kind, path, app, tenant, cc, dev FROM la_events WHERE kind IN ('checkout_error', 'signup_error') ORDER BY at DESC LIMIT 40")->fetchAll()));
$o['la_checkouts'] = $s(fn() => q("SELECT kind, path, COUNT(*) n FROM la_events WHERE kind IN ('checkout', 'signup', 'checkout_error', 'signup_error') AND at > ? GROUP BY kind, path ORDER BY n DESC LIMIT 30", [now_ms() - 7 * 86400000])->fetchAll());
$o['la_alerts'] = $s(fn() => array_map(fn($r) => [gmdate('m-d H:i', intdiv((int)$r['at'], 1000)), $r['kind'], mb_substr((string)$r['text'], 0, 220)], q('SELECT at, kind, text FROM la_alerts ORDER BY id DESC LIMIT 10')->fetchAll()));
$o['plans'] = $s(fn() => array_map(fn($p) => ['code' => $p->code ?? '', 'aud' => $p->audience ?? '', 'price' => $p->base_price ?? null, 'active' => $p->active ?? null, 'trial' => $p->trial_days ?? null, 'feats' => is_array($p->features ?? null) ? count($p->features) : (is_object($p->features ?? null) ? count((array)$p->features) : ($p->features ?? null)), 'keys' => implode(',', array_keys((array)$p))], bill_platform_rows('plans')));
$o['tenants'] = $s(fn() => q("SELECT app, status, COUNT(*) n FROM tenants GROUP BY app, status ORDER BY app")->fetchAll());
$o['txns'] = $s(fn() => array_map(fn($r) => [gmdate('m-d H:i', intdiv((int)($r['at'] ?? $r['created'] ?? 0), 1000)), $r['status'] ?? '', $r['kind'] ?? '', substr((string)($r['tenant'] ?? ''), -6), mb_substr((string)($r['data'] ?? ''), 0, 260)], q('SELECT * FROM pay_txns ORDER BY rowid DESC LIMIT 12')->fetchAll()));
$o['notices'] = $s(fn() => array_map(fn($r) => mb_substr(jenc($r), 0, 300), q("SELECT * FROM meta WHERE k LIKE 'rq_cache:%' ORDER BY k LIMIT 60")->fetchAll()));
$o['partner_offers'] = $s(function () { $n = 0; $x = []; foreach (q("SELECT id FROM tenants WHERE app = 'partner'")->fetchAll() as $r) { foreach (bill_rows((string)$r['id'], 'db:' . $r['id'] . ':p_offers') as $of) { $n++; if (count($x) < 5) $x[] = array_keys((array)$of); } } return [$n, $x]; });
$o['feat_soon_auto'] = $s(fn() => function_exists('feat_states_all') ? feat_states_all() : null);
$o['renew'] = $s(fn() => bill_cfg()['renew'] ?? null);
$o['wa'] = $s(fn() => function_exists('wag_cfg') ? array_intersect_key((array)wag_cfg(), array_flip(['on', 'base', 'state', 'phone', 'mode'])) : 'no fn');
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
