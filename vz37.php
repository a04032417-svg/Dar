<?php
// Dar — read-only look: what the platform's own doctor holds open right now (counts, then each open finding: code, layer, level, status, times seen) and the browsers' error reports since the last release. Nothing is changed; no person's data is printed.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php'; require_once $R . '/dar-doctor.php';
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
try { $o['counts'] = doc_counts(); $rows = [];
    foreach (q("SELECT code, subject, layer, level, status, seen, recur, tries, last_at, first_at FROM doc_inc WHERE status IN ('open','waiting','stuck','fixing') ORDER BY last_at DESC LIMIT 60")->fetchAll() as $r)
        $rows[] = [$r['code'], substr((string)$r['subject'], 0, 40), $r['layer'], $r['level'], $r['status'], (int)$r['seen'], (int)$r['recur'], (int)$r['tries'], gmdate('m-d H:i', (int)($r['last_at'] / 1000)), gmdate('m-d H:i', (int)($r['first_at'] / 1000))];
    $o['open'] = $rows;
    $o['fixed_recent'] = array_map(fn($r) => [$r['code'], $r['status'], $r['fixed_by'], gmdate('m-d H:i', (int)($r['fixed_at'] / 1000))], q("SELECT code, status, fixed_by, fixed_at FROM doc_inc WHERE status IN ('fixed','gone') ORDER BY fixed_at DESC LIMIT 12")->fetchAll());
} catch (Throwable $e) { $o['doc_err'] = substr($e->getMessage(), 0, 200); }
$since = strtotime('2026-10-29 05:00:00 UTC'); $v = []; $f = $R . '/.dar/errors.log';
if (is_file($f)) { $sz = filesize($f); $fh = fopen($f, 'rb'); fseek($fh, max(0, $sz - 400000)); $t = (string)stream_get_contents($fh); fclose($fh);
    foreach (explode("\n", $t) as $l) { $j = json_decode($l, true); if (!is_array($j) || ($j['sev'] ?? '') === 'selftest') continue; $at = strtotime((string)($j['t'] ?? $j['at'] ?? '')) ?: 0; if ($at < $since) continue; $v[] = [gmdate('m-d H:i', $at), substr((string)($j['v'] ?? ''), 0, 14), substr((string)($j['msg'] ?? ''), 0, 140), substr((string)($j['where'] ?? ''), 0, 50)]; } }
$o['visitor_since_release_n'] = count($v); $o['visitor_since_release'] = array_slice($v, -25);
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
