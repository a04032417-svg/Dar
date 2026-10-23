<?php
// Dar — a read-only look at what failed lately on the live site (nothing is changed): the server's errors, the visitors' page errors,
// the AI functions that failed, the WhatsApp numbers' state, the newest accounts. Run once by a temporary cron job, then removed.
$R = '/home/u791571885/domains/dar.ink/public_html';
require $R . '/dar-sync.php';
$H = (int)($argv[1] ?? 48) * 3600; $o = [];
$o['v'] = SYNC_VERSION;
$cut = fn($g) => array_map(fn($x) => [$x['n'], $x['last'], $x['where'], mb_substr($x['msg'], 0, 160), $x['app'] ?? ''], array_slice($g, 0, 25));
$se = log_tail(data_dir() . '/server-errors.log', $H); $o['server'] = ['n' => $se['n'], 'g' => $cut($se['groups'])];
$ve = log_tail($R . '/.dar/errors.log', $H, 'error'); $o['pages'] = ['n' => $ve['n'], 'g' => $cut($ve['groups'])];
// the pages' own lines carry the route: the newest few whole
$lines = @file($R . '/.dar/errors.log') ?: []; $o['pages_last'] = array_map(fn($l) => mb_substr(trim($l), 0, 330), array_slice($lines, -12));
try { $since = (time() - $H) * 1000; $o['ai'] = q("SELECT fn, scope, SUM(ok = 0) bad, COUNT(*) n, GROUP_CONCAT(DISTINCT code) codes FROM lrn_calls WHERE at > ? GROUP BY fn, scope HAVING bad > 0 ORDER BY bad DESC LIMIT 25", [$since])->fetchAll(); } catch (Throwable $e) { $o['ai'] = $e->getMessage(); }
try { $o['wa'] = q("SELECT sid, ns, status, phone, at FROM wag_sessions ORDER BY at DESC LIMIT 12")->fetchAll(); foreach ($o['wa'] as &$w) $w['phone'] = $w['phone'] ? '…' . substr((string)$w['phone'], -4) : ''; unset($w); } catch (Throwable $e) { $o['wa'] = $e->getMessage(); }
try { $o['tenants'] = array_map(function ($r) { $T = jdec((string)$r['rec']); return [$r['id'], is_object($T) ? ($T->name ?? '') : '', is_object($T) ? ($T->app ?? '') : '', $r['status'], is_object($T) ? ($T->plan ?? '') : '', is_object($T) ? ($T->created ?? '') : '']; }, q("SELECT id, status, rec FROM tenants ORDER BY rowid DESC LIMIT 12")->fetchAll()); } catch (Throwable $e) { $o['tenants'] = $e->getMessage(); }
try { $o['jobs_failed'] = q("SELECT COUNT(*) n FROM jobs WHERE status = 'failed'")->fetch()['n'] ?? null; } catch (Throwable $e) { $o['jobs_failed'] = 'n/a'; }
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
