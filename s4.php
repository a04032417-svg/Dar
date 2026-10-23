<?php
// Read only (the owner's notes 2 and 46 — «المساعد لا زال غبياً ولا ينفذ كل ما يُطلب منه أو يكذب»): what the owner asked his assistant
// lately, what it answered and did, and how the AI providers behaved in the last 14 days — to find where it fails. Keys are masked
// before anything is printed; only the owner's own conversations (the developer panel) are read. Nothing is changed.
$W = '/home/u791571885/domains/dar.ink/public_html'; chdir($W); require $W . '/dar-sync.php';
$mask = function (string $s): string { if (function_exists('keys_scrub_all')) return keys_scrub_all($s); if (function_exists('keys_scrub_text')) { $f = []; return keys_scrub_text($s, $f); } return preg_replace('/[A-Za-z0-9_\-]{28,}/', '[…]', $s); };
$cut = fn(string $s, int $n) => mb_substr(trim((string)preg_replace('/\s+/u', ' ', $s)), 0, $n);
$out = ['at' => gmdate('c'), 'version' => defined('SYNC_VERSION') ? SYNC_VERSION : '?'];
$since = now_ms() - 14 * 86400000;
try {
    foreach (q('SELECT provider, model, ok, code, COUNT(*) n, CAST(AVG(ms) AS INTEGER) ms FROM ai_log WHERE at > ? GROUP BY provider, model, ok, code ORDER BY n DESC LIMIT 40', [$since])->fetchAll() as $r)
        $out['calls'][] = $r['provider'] . ' ' . $r['model'] . ' ' . ($r['ok'] ? 'ok' : 'FAIL ' . $r['code']) . ' ×' . $r['n'] . ' ~' . $r['ms'] . 'ms';
} catch (Throwable $e) { $out['calls_err'] = $e->getMessage(); }
try {
    foreach (q("SELECT id, surface, title, n, updated FROM mind_thread WHERE app = 'developer' ORDER BY updated DESC LIMIT 5")->fetchAll() as $t) {
        $msgs = array_reverse(q('SELECT role, text, meta, at FROM mind_msg WHERE thread = ? ORDER BY id DESC LIMIT 24', [$t['id']])->fetchAll());
        $th = ['title' => $cut($mask((string)$t['title']), 80), 'surface' => $t['surface'], 'n' => (int)$t['n'], 'updated' => gmdate('c', (int)($t['updated'] / 1000)), 'msgs' => []];
        foreach ($msgs as $m) {
            $meta = json_decode((string)$m['meta'], true); $mm = '';
            if (is_array($meta)) { $bits = []; foreach (['provider', 'model', 'fn', 'tier', 'by'] as $k) if (isset($meta[$k]) && is_scalar($meta[$k])) $bits[] = $k . '=' . $cut((string)$meta[$k], 40);
                foreach (['plan', 'done', 'failed', 'steps', 'ran', 'pending'] as $k) if (isset($meta[$k]) && is_array($meta[$k])) $bits[] = $k . ':' . count($meta[$k]);
                if (!$bits) $bits[] = 'keys:' . implode(',', array_slice(array_keys($meta), 0, 8)); $mm = ' {' . implode(' ', $bits) . '}'; }
            $th['msgs'][] = gmdate('m-d H:i', (int)($m['at'] / 1000)) . ' ' . ($m['role'] === 'user' ? 'U' : 'A') . ': ' . $cut($mask((string)$m['text']), $m['role'] === 'user' ? 300 : 420) . $mm;
        }
        $out['threads'][] = $th;
    }
} catch (Throwable $e) { $out['threads_err'] = $e->getMessage(); }
try { foreach (q("SELECT fn, kind, rule, used, active, at FROM lrn_corr ORDER BY at DESC LIMIT 15")->fetchAll() as $r) $out['corrections'][] = gmdate('m-d', (int)($r['at'] / 1000)) . ' ' . $r['fn'] . ' ' . $r['kind'] . ' used=' . $r['used'] . ($r['active'] ? '' : ' (off)') . ': ' . $cut($mask((string)$r['rule']), 200); } catch (Throwable $e) { $out['corr_err'] = $e->getMessage(); }
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n";
