<?php
// The funnel as the live records say it: each business that opened an account (no names, no addresses — a hash and its shape), what
// it did, where it stands; the payments; the last 7 days of visits by source and page; the free tools' use
ini_set('display_errors', '0'); @set_time_limit(120);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION, 'now' => gmdate('c')]; $d = fn($ms) => $ms ? gmdate('m-d H:i', intdiv((int)$ms, 1000)) : '';
foreach (q('SELECT id, app, status, rec, created FROM tenants ORDER BY created')->fetchAll() as $t) {
  $r = json_decode((string)$t['rec'], true) ?: []; $us = q('SELECT id, email, phone, role, status, created FROM users WHERE tenant = ?', [$t['id']])->fetchAll();
  $last = 0; foreach ($us as $u) { $s = one('SELECT MAX(created) m FROM sessions WHERE uid = ?', [$u['id']]); $last = max($last, (int)($s['m'] ?? 0)); }
  $dom = array_map(fn($u) => substr(strrchr((string)$u['email'], '@') ?: '', 1), $us); $cc = array_map(fn($u) => substr(preg_replace('/\D/', '', (string)$u['phone']), 0, 3), $us);
  $kv = one('SELECT COUNT(*) n, SUM(sz) s, MAX(upd) u FROM kv WHERE ns = ?', [$t['id']]) ?: []; $recs = (int)($kv['n'] ?? 0); $recs2 = (int)($kv['s'] ?? 0); $kvu = (int)($kv['u'] ?? 0);
  $ks = array_column(q('SELECT k FROM kv WHERE ns = ? LIMIT 12', [$t['id']])->fetchAll(), 'k');
  $o['tenants'][] = ['h' => substr(md5($t['id']), 0, 6), 'app' => $t['app'], 'st' => $t['status'], 'plan' => $r['plan'] ?? '', 'cycle' => $r['cycle'] ?? '', 'created' => $d($t['created']), 'trial_end' => (string)($r['trial_end'] ?? ''), 'renews' => (string)($r['renews_at'] ?? ''), 'why' => (string)($r['suspend_reason'] ?? ''), 'due' => (string)($r['due_reason'] ?? ''), 'ref' => (string)($r['ref'] ?? ($r['src'] ?? '')), 'country' => (string)($r['country'] ?? ''), 'users' => count($us), 'dom' => implode(',', array_unique($dom)), 'cc' => implode(',', array_unique($cc)), 'last' => $d($last), 'kv' => $recs, 'kvsz' => $recs2, 'kvupd' => $d($kvu), 'kvk' => implode(',', $ks), 'keys' => implode(',', array_slice(array_keys($r), 0, 40))];
}
try { $o['payments'] = array_map(fn($p) => ['kind' => $p['kind'], 'st' => $p['status'], 'amt' => $p['amount'], 'cur' => $p['currency'], 'at' => $d($p['at'])], q('SELECT kind, status, amount, currency, at FROM payments ORDER BY at DESC LIMIT 20')->fetchAll()); } catch (Throwable $e) { $o['payments'] = 'x'; }
try { $o['subs_log'] = array_map(fn($p) => ['h' => substr(md5($p['tenant']), 0, 6), 'act' => $p['action'], 'plan' => $p['plan'], 'at' => $d($p['at'])], q('SELECT tenant, action, plan, at FROM subs_log ORDER BY at DESC LIMIT 25')->fetchAll()); } catch (Throwable $e) { $o['subs_log'] = 'x'; }
try { $o['requests'] = q('SELECT status, COUNT(*) n FROM subs_requests GROUP BY status')->fetchAll(); } catch (Throwable $e) {}
$since = (time() - 7 * 86400) * 1000;
try { $o['la_cols'] = array_column(q("PRAGMA table_info(la_events)")->fetchAll(), 'name');
  $o['la_kinds'] = q('SELECT kind, COUNT(*) n FROM la_events WHERE at > ? GROUP BY kind ORDER BY n DESC', [$since])->fetchAll();
  $o['la_src'] = q("SELECT src, ref, COUNT(*) n FROM la_events WHERE at > ? AND land = 1 GROUP BY src, ref ORDER BY n DESC LIMIT 20", [$since])->fetchAll();
  $o['la_path'] = q("SELECT path, COUNT(*) n FROM la_events WHERE at > ? AND kind = 'view' GROUP BY path ORDER BY n DESC LIMIT 25", [$since])->fetchAll();
  $o['la_day'] = q("SELECT day, COUNT(*) n FROM la_events WHERE at > ? AND land = 1 GROUP BY day ORDER BY day", [$since])->fetchAll(); } catch (Throwable $e) { $o['la_err'] = $e->getMessage(); }
try { $o['plans'] = array_map(fn($p) => ['code' => $p['code'] ?? '', 'app' => $p['app'] ?? '', 'price' => $p['base_price'] ?? '', 'cur' => $p['currency'] ?? '', 'trial' => $p['trial_days'] ?? '', 'name' => is_array($p['name'] ?? null) ? ($p['name']['ar'] ?? '') : ($p['name'] ?? '')], array_merge(grow_plans('office'), grow_plans('builder'))); } catch (Throwable $e) { $o['plans'] = 'x'; }
try { $o['trial_days'] = function_exists('grow_trial') ? ['office' => grow_trial('office'), 'builder' => grow_trial('builder')] : null; } catch (Throwable $e) {}
try { $o['free_use'] = q("SELECT k, COUNT(*) n FROM hits WHERE k LIKE 'free_%' GROUP BY k")->fetchAll(); } catch (Throwable $e) { $o['free_use'] = 'x:' . $e->getMessage(); }
try { $o['isites'] = ['made' => (int)(one("SELECT COUNT(*) n FROM meta WHERE k LIKE 'gs:%'")['n'] ?? 0), 'taken' => (int)(one("SELECT COUNT(*) n FROM meta WHERE k LIKE 'gs:%' AND v LIKE '%\"t\":\"%' AND v NOT LIKE '%\"t\":\"\"%'")['n'] ?? 0)]; } catch (Throwable $e) {}
try { $o['cards'] = (int)(one("SELECT COUNT(*) n FROM meta WHERE k LIKE 'cj:%'")['n'] ?? 0); } catch (Throwable $e) {}
echo json_encode($o, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
