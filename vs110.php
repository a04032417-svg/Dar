<?php
// After 3.33.69: no subscriber's website holds a bank photograph (every office's records and published site scanned, and the trial
// sites taken by an account); a design made the subscriber's way gets photographs made for it alone (Horde) and none of them is in the
// bank — tried on a sample design kept in the platform's own files, never an office's
ini_set('display_errors', '0'); @set_time_limit(300);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION]; $bank = [];
foreach (q('SELECT url FROM pbank')->fetchAll() as $r) if (preg_match('/f=([0-9a-f]{48})/', (string)$r['url'], $m)) $bank[$m[1]] = 1;
$o['bank'] = count($bank); $hit = [];
foreach (['kv', 'pub'] as $t) { try { foreach (q("SELECT ns, v FROM $t WHERE v LIKE '%f=%'")->fetchAll() as $row) if (preg_match_all('/f=([0-9a-f]{48})/', (string)$row['v'], $mm)) foreach ($mm[1] as $x) if (isset($bank[$x]) && $row['ns'] !== 'platform') $hit[$t . ':' . substr(md5((string)$row['ns']), 0, 6)] = ($hit[$t . ':' . substr(md5((string)$row['ns']), 0, 6)] ?? 0) + 1; } catch (Throwable $e) { $o['scan_err'][] = $t . ' ' . substr($e->getMessage(), 0, 80); } }
$o['subscriber_rows_with_bank_photos'] = $hit;
$taken = 0; $takenBank = 0; foreach (q("SELECT v FROM meta WHERE k LIKE 'gs:%'")->fetchAll() as $row) { $r = o2a(jdec((string)$row['v'])); if (!is_array($r) || ($r['t'] ?? '') === '') continue; $taken++; if (preg_match_all('/f=([0-9a-f]{48})/', json_encode($r['d'] ?? []), $mm)) foreach ($mm[1] as $x) if (isset($bank[$x])) $takenBank++; }
$o['trials_taken'] = $taken; $o['taken_with_bank'] = $takenBank;
// a design made the subscriber's way
$d = ['pages' => [['path' => '', 'sections' => [['html' => '<section>{{img contemporary villa facade with a landscaped garden, north Riyadh, Najdi architecture | wide}}</section>'], ['html' => '<div>{{img bright modern apartment living room with large windows, Riyadh | square}}{{img empty residential plot ready for building, east Riyadh | square}}{{img calm modern real-estate office interior with warm wood | tall}}</div>']]]]];
$t0 = microtime(true); $n = webfree_images($d, grow_owner(), 40, 8, false); $all = json_encode($d, JSON_UNESCAPED_SLASHES);
preg_match_all('/f=([0-9a-f]{48})/', $all, $mm); $o['own'] = ['made' => $n, 'secs' => round(microtime(true) - $t0, 1), 'left' => preg_match_all('/\{\{\s*img/', $all), 'in_bank' => count(array_filter($mm[1], fn($x) => isset($bank[$x]))), 'bank_after' => pbank_count()];
foreach (array_slice($mm[1], 0, 2) as $x) { $b = @file_get_contents(file_path($x)); if ($b && ($im = @imagecreatefromstring($b))) { $w = imagesx($im); $h = imagesy($im); $nw = 280; $nh = (int)round($h * $nw / max(1, $w)); $t = imagecreatetruecolor($nw, $nh); imagecopyresampled($t, $im, 0, 0, 0, 0, $nw, $nh, $w, $h); ob_start(); imagejpeg($t, null, 70); $o['thumbs'][] = base64_encode((string)ob_get_clean()); } }
echo json_encode($o, JSON_UNESCAPED_SLASHES), "\n";
