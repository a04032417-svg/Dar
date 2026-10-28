<?php
// Dar — read-only look at the live site (2026-10-28, after 3.33.29): the version, which AI providers are on and how many keys each holds (counts only — never a key),
// the doctor's open incidents (codes only), the worldwide offices lookup for a few places, and the WhatsApp gateway's state. Changes nothing.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
$s = function (callable $f) { try { return $f(); } catch (Throwable $e) { return 'ERR ' . substr($e->getMessage(), 0, 200); } };
$o['ai'] = $s(function () { $c = ai_cfg(); $x = []; foreach ($c['order'] as $id) { $x[$id] = ['on' => (bool)$c['providers'][$id]['enabled'], 'keys' => count(ai_keys($id)), 'open_keyless' => in_array($id, AI_OPEN, true)]; } return ['retired' => array_keys($c['retired']), 'providers' => $x]; });
$o['incidents'] = $s(function () { doc_db(); $x = []; foreach (q("SELECT code, level, status, COUNT(*) c FROM doc_inc WHERE status IN ('open','waiting','failed','fixing','queued') GROUP BY code, level, status")->fetchAll() as $r) $x[] = $r['code'] . '/' . $r['level'] . '/' . $r['status'] . ' x' . $r['c']; return $x; });
$o['wag_sessions'] = $s(function () { wag_db(); $x = []; foreach (q('SELECT status, COUNT(*) c FROM wag_sessions GROUP BY status')->fetchAll() as $r) $x[$r['status']] = (int)$r['c']; return $x; });
$cities = [['Riyadh', 24.7136, 46.6753, 5000], ['Dubai', 25.2048, 55.2708, 5000], ['Cairo', 30.0444, 31.2357, 5000], ['Paris', 48.8566, 2.3522, 5000], ['Jakarta', -6.2088, 106.8456, 5000]];
$o['offices'] = [];
foreach ($cities as [$n, $la, $lo, $r]) {
    $t0 = microtime(true);
    $x = $s(fn() => pl_offices_lookup((float)$la, (float)$lo, (int)$r));
    if (is_array($x)) {
        $lines = array_values(array_filter(explode("\n", (string)$x['text']), fn($l) => $l !== '' && !preg_match('/^@?type\t/', $l)));
        $o['offices'][$n] = ['s' => round(microtime(true) - $t0, 1), 'any' => $x['any'], 'who' => array_keys((array)$x['who']), 'n' => count($lines)];
    } else $o['offices'][$n] = $x;
}
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
