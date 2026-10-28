<?php
// Dar — read-only look at the live site (2026-10-28, after 3.33.27): the worldwide offices lookup of the installed code, one ask per place (a few
// cities on every continent and one small town), and the WhatsApp gateway's state. Changes nothing (the lookup only asks public maps).
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
$s = function (callable $f) { try { return $f(); } catch (Throwable $e) { return 'ERR ' . substr($e->getMessage(), 0, 200); } };
$o['wag_cfg'] = $s(fn() => array_intersect_key((array)wag_cfg(), array_flip(['on', 'url', 'state', 'mode', 'ready'])));
$o['wag_sessions'] = $s(function () { wag_db(); $x = []; foreach (q('SELECT status, COUNT(*) c FROM wag_sessions GROUP BY status')->fetchAll() as $r) $x[$r['status']] = (int)$r['c']; return $x; });
$cities = [['Riyadh', 24.7136, 46.6753, 5000], ['Dammam', 26.4207, 50.0888, 5000], ['Paris', 48.8566, 2.3522, 5000], ['Lagos', 6.5244, 3.3792, 5000], ['Jakarta', -6.2088, 106.8456, 5000], ['SaoPaulo', -23.5505, -46.6333, 5000], ['Chicago', 41.8781, -87.6298, 5000], ['Tanta(small town)', 30.7865, 31.0004, 5000]];
$o['offices'] = [];
foreach ($cities as [$n, $la, $lo, $r]) {
    $t0 = microtime(true);
    $x = $s(fn() => pl_offices_lookup((float)$la, (float)$lo, (int)$r));
    if (is_array($x)) {
        $lines = array_values(array_filter(explode("\n", (string)$x['text']), fn($l) => $l !== '' && !preg_match('/^@?type\t/', $l)));
        $names = []; foreach (array_slice($lines, 0, 3) as $l) { $c = explode("\t", $l); $names[] = (string)($c[4] ?? $c[5] ?? ''); }
        $o['offices'][$n] = ['s' => round(microtime(true) - $t0, 1), 'any' => $x['any'], 'whole' => $x['whole'], 'who' => array_keys((array)$x['who']), 'why' => $x['why'], 'n' => count($lines), 'first' => $names];
    } else $o['offices'][$n] = $x;
}
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
