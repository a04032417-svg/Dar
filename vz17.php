<?php
// Dar — read-only look at the live site (2026-10-28, after 3.33.30): the version, which AI providers are on and how many keys each holds (counts only — never a key),
// the AI quality gate's record, one real Arabic answer and one code answer through the live gateway (what came back and how fast), the doctor's open incidents (codes only),
// the public pages' memory (count of kept pages) and the WhatsApp gateway's state. Changes nothing but the usual counters of one AI call.
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
$s = function (callable $f) { try { return $f(); } catch (Throwable $e) { return 'ERR ' . substr($e->getMessage(), 0, 200); } };
$o['ai'] = $s(function () { $c = ai_cfg(); $x = []; foreach ($c['order'] as $id) { $x[$id] = ['on' => (bool)$c['providers'][$id]['enabled'], 'keys' => count(ai_keys($id)), 'open_keyless' => in_array($id, AI_OPEN, true)]; } return ['retired' => array_keys($c['retired']), 'providers' => $x]; });
$o['gate'] = $s(fn() => function_exists('ai_gate_read') ? ai_gate_read() : 'absent');
$live = function (string $sys, string $usr, int $tok) use ($s) {
    return $s(function () use ($sys, $usr, $tok) { $c = doc_owner(); if (!$c) return 'no owner context'; $t0 = microtime(true);
        $r = ai_complete($c, ['messages' => [['role' => 'system', 'content' => $sys], ['role' => 'user', 'content' => $usr]], 'tier' => 'standard', 'temperature' => 0.2, 'max_tokens' => $tok, 'deadline' => 40, 'cache' => false]);
        return ['s' => round(microtime(true) - $t0, 1), 'provider' => $r['provider'] ?? '?', 'model' => $r['model'] ?? '?', 'chars' => mb_strlen((string)($r['text'] ?? '')), 'text' => mb_substr((string)($r['text'] ?? ''), 0, 160)]; });
};
$o['live_ar'] = $live('Answer in Arabic only. No symbols, no code.', 'اكتب جملة ترحيب قصيرة بالعربية لمكتب عقاري.', 200);
$o['live_code'] = $live('Answer with code only, no explanation.', 'Write a PHP function add($a, $b) that returns the sum of two numbers.', 300);
$o['incidents'] = $s(function () { doc_db(); $x = []; foreach (q("SELECT code, level, status, COUNT(*) c FROM doc_inc WHERE status IN ('open','waiting','failed','fixing','queued') GROUP BY code, level, status")->fetchAll() as $r) $x[] = $r['code'] . '/' . $r['level'] . '/' . $r['status'] . ' x' . $r['c']; return $x; });
$o['wag_sessions'] = $s(function () { wag_db(); $x = []; foreach (q('SELECT status, COUNT(*) c FROM wag_sessions GROUP BY status')->fetchAll() as $r) $x[$r['status']] = (int)$r['c']; return $x; });
$o['webcache_files'] = $s(function () { $d = data_dir() . '/webcache'; return is_dir($d) ? count(glob($d . '/*.wc') ?: []) : 0; });
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
