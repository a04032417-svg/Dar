<?php
// Dar — read-only look: every error the browsers reported (with the version each ran) and the server's own errors of the last fortnight. Nothing is changed; no person's data is printed.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
foreach (['visitor' => [$R . '/.dar/errors.log', $R . '/.dar/errors.1.log'], 'server' => [data_dir() . '/server-errors.log']] as $k => $files) { $rows = [];
    foreach ($files as $f) { if (!is_file($f)) continue; $sz = filesize($f); $fh = fopen($f, 'rb'); fseek($fh, max(0, $sz - 700000)); $t = (string)stream_get_contents($fh); fclose($fh);
        foreach (explode("\n", $t) as $l) { $j = json_decode($l, true); if (!is_array($j) || ($j['sev'] ?? '') === 'selftest') continue; $at = strtotime((string)($j['t'] ?? $j['at'] ?? '')) ?: 0; if ($at && $at < time() - 14 * 86400) continue;
            $rows[] = [gmdate('m-d H:i', $at), substr((string)($j['v'] ?? ''), 0, 14), substr((string)($j['sev'] ?? ''), 0, 5), substr((string)($j['msg'] ?? ''), 0, 150), substr((string)($j['where'] ?? ''), 0, 50), substr((string)($j['route'] ?? ''), 0, 40), (int)($j['count'] ?? 1)]; } }
    $o[$k . '_n'] = count($rows); $o[$k] = array_slice($rows, -45); }
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
