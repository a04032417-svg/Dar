<?php
// Read only: why the live assets/i18n.js differs from the 3.32.1 package before 3.33.0 replaces it — the site's own record of code
// edits (code_hist: who, when, what) and the lines that differ from the copy kept before the latest edit. Nothing is changed.
$W = '/home/u791571885/domains/dar.ink/public_html'; $D = dirname($W) . '/dar-data';
$p = new PDO('sqlite:' . $D . '/dar.sqlite'); $out = [];
try { $out['code_hist'] = $p->query("SELECT id, path, action, bak, at, uid, size_before, size_after FROM code_hist ORDER BY id DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) { $out['code_hist_err'] = $e->getMessage(); }
$cur = (string)@file_get_contents($W . '/assets/i18n.js'); $out['i18n'] = ['size' => strlen($cur), 'mtime' => gmdate('c', (int)@filemtime($W . '/assets/i18n.js'))];
$bak = '';
foreach ($out['code_hist'] ?? [] as $h) if (($h['path'] ?? '') === 'assets/i18n.js' && ($h['bak'] ?? '') !== '') { $b = (string)$h['bak']; foreach ([$D . '/code-history/' . $b, $b, $D . '/' . $b] as $f) if (is_file($f)) { $bak = $f; break 2; } }
$out['bak_found'] = $bak !== '' ? basename($bak) : '';
if ($bak !== '') {
    $a = explode("\n", (string)file_get_contents($bak)); $c = explode("\n", $cur); $diff = [];
    $n = max(count($a), count($c)); for ($i = 0, $j = 0; ($i < count($a) || $j < count($c)) && count($diff) < 40; ) {
        if (($a[$i] ?? null) === ($c[$j] ?? null)) { $i++; $j++; continue; }
        // a line changed, added or removed: look a little ahead to re-align
        $k = array_search($a[$i] ?? '', array_slice($c, $j, 30), true); $m = array_search($c[$j] ?? '', array_slice($a, $i, 30), true);
        if ($k !== false && ($m === false || $k <= $m)) { for ($x = 0; $x < $k; $x++) $diff[] = '+' . ($j + $x + 1) . ': ' . mb_substr($c[$j + $x], 0, 300); $j += $k; }
        elseif ($m !== false) { for ($x = 0; $x < $m; $x++) $diff[] = '-' . ($i + $x + 1) . ': ' . mb_substr($a[$i + $x], 0, 300); $i += $m; }
        else { $diff[] = '-' . ($i + 1) . ': ' . mb_substr($a[$i] ?? '', 0, 300); $diff[] = '+' . ($j + 1) . ': ' . mb_substr($c[$j] ?? '', 0, 300); $i++; $j++; }
    }
    $out['diff'] = $diff;
}
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n";
