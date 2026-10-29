<?php
// Dar — read-only look at what the platform's own doctor and error logs hold (2026-10-29): the open findings (the «30 errors»), the latest fixes it made, and the browsers' own error reports.
// Nothing is changed; no key, secret, number or name of a person is printed.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php'; foreach (['dar-doctor.php'] as $f) if (!function_exists('doc_counts') && is_file($R . '/' . $f)) require_once $R . '/' . $f;
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
$s = function (string $label, callable $f) use (&$o) { try { $o[$label] = $f(); } catch (Throwable $e) { $o[$label] = 'ERR ' . substr($e->getMessage(), 0, 200); } };
$s('counts', fn() => doc_counts());
$s('open', function () { doc_db(); $r = []; foreach (q("SELECT code, subject, level, status, seen, tries, remedy, first_at, last_at, info FROM doc_inc WHERE status IN ('open','waiting','stuck','fixing') ORDER BY CASE level WHEN 'red' THEN 0 ELSE 1 END, last_at DESC LIMIT 80")->fetchAll() as $x) $r[] = [$x['level'], $x['status'], $x['code'], substr((string)$x['subject'], 0, 50), (int)$x['seen'], (int)$x['tries'], explode(':', (string)$x['remedy'])[0], gmdate('m-d H:i', (int)($x['last_at'] / 1000)), substr(preg_replace('/\s+/', ' ', (string)$x['info']), 0, 140)]; return $r; });
$s('by_code', function () { doc_db(); $r = []; foreach (q("SELECT code, level, status, COUNT(*) n FROM doc_inc WHERE status IN ('open','waiting','stuck','fixing') GROUP BY code, level, status ORDER BY n DESC")->fetchAll() as $x) $r[] = [$x['code'], $x['level'], $x['status'], (int)$x['n']]; return $r; });
$s('fixes', function () { doc_db(); $r = []; foreach (q('SELECT code, remedy, ok, verified, at FROM doc_fix ORDER BY at DESC LIMIT 10')->fetchAll() as $x) $r[] = [$x['code'], explode(':', (string)$x['remedy'])[0], (int)$x['ok'], (int)$x['verified'], gmdate('m-d H:i', (int)($x['at'] / 1000))]; return $r; });
$grp = function (string $f, int $days) { if (!is_file($f)) return 'no log'; $sz = filesize($f); $fh = fopen($f, 'rb'); fseek($fh, max(0, $sz - 500000)); $t = (string)stream_get_contents($fh); fclose($fh); $g = []; $cut = time() - $days * 86400; $tot = 0;
    foreach (explode("\n", $t) as $l) { $j = json_decode($l, true); if (!is_array($j)) continue; $at = strtotime((string)($j['t'] ?? $j['at'] ?? '')) ?: 0; if ($at && $at < $cut) continue; $n = max(1, (int)($j['count'] ?? 1)); $tot += $n; $k = substr((string)($j['sev'] ?? ''), 0, 8) . ' | ' . substr((string)($j['msg'] ?? ''), 0, 130) . ' | ' . substr((string)($j['where'] ?? ''), 0, 60) . ' | ' . substr((string)($j['route'] ?? ''), 0, 40); $g[$k] = ($g[$k] ?? 0) + $n; }
    arsort($g); return ['total' => $tot, 'top' => array_slice($g, 0, 25, true)]; };
$s('visitor_errors_7d', fn() => $grp($R . '/.dar/errors.log', 7));
$s('server_errors_3d', fn() => $grp(data_dir() . '/server-errors.log', 3));
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
