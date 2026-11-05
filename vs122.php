<?php
// probe: the trial site's photographs since (the cron job), and how long the image network makes an anonymous photograph wait now
ini_set('display_errors', '0'); @set_time_limit(400);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION];
$all = (string)meta_get('gs:mw3rqekr42c945'); preg_match_all('/dar-sync\.php\?f=([0-9a-f]{48})/', $all, $fm);
$o['site'] = ['photos' => count(array_unique($fm[1])), 'left' => preg_match_all('/\{\{\s*img\b/', $all)];
$o['ex_photo_at'] = (int)(meta_get('ex_photo_at') ?? 0) - time(); $o['noup'] = (int)(meta_get('photo_noup') ?? 0) - time();
try { $o['faults'] = array_map(fn($r) => mb_substr(implode(' | ', array_map('strval', $r)), 0, 220), q("SELECT * FROM faults WHERE at > ? AND (msg LIKE '%photo%' OR msg LIKE '%art%' OR ctx LIKE '%art%' OR ctx LIKE '%photo%') ORDER BY at DESC LIMIT 5", [(time() - 3600) * 1000])->fetchAll()); } catch (Throwable $e) { $o['faults'] = 'x:' . $e->getMessage(); }
[$c, $res] = ai_http('GET', PHOTO_HORDE . '/status/models?type=image&model_state=known', photo_hd(), null, 20); $m = json_decode((string)$res, true) ?: [];
foreach ($m as $x) if (in_array($x['name'] ?? '', PHOTO_MODELS, true)) $o['models'][$x['name']] = ['w' => $x['count'] ?? 0, 'q' => $x['queued'] ?? 0, 'eta' => $x['eta'] ?? 0, 'jobs' => $x['jobs'] ?? 0];
$t0 = time(); $ids = photo_submit(['a' => ['a contemporary villa facade with a landscaped front garden, contemporary Najdi architecture of Riyadh, at golden hour', 'wide']]);
$o['submitted'] = $ids ? 1 : 0; $trace = [];
if ($ids) { $id = $ids['a'];
  while (time() - $t0 < 330) { sleep(6); [$c, $res] = ai_http('GET', PHOTO_HORDE . '/generate/check/' . $id, photo_hd(), null, 10); $j = json_decode((string)$res, true) ?: [];
    $trace[] = (time() - $t0) . 's q' . ($j['queue_position'] ?? '?') . ' w' . ($j['wait_time'] ?? '?') . ' p' . ($j['processing'] ?? '?') . ($j['done'] ?? false ? ' DONE' : '') . ((($j['is_possible'] ?? true) === false) ? ' IMPOSSIBLE' : '');
    if (!empty($j['done']) || ($j['is_possible'] ?? true) === false || !empty($j['faulted'])) break; }
  $o['secs'] = time() - $t0;
  if (str_contains((string)end($trace), 'DONE')) { [$c, $res] = ai_http('GET', PHOTO_HORDE . '/generate/status/' . $id, photo_hd(), null, 15); $g = (json_decode((string)$res, true) ?: [])['generations'][0] ?? []; $o['gen'] = ['model' => $g['model'] ?? '', 'censored' => $g['censored'] ?? null, 'worker' => $g['worker_name'] ?? '']; if (is_string($g['img'] ?? null)) { [$c2, $b] = ai_http('GET', $g['img'], [], null, 25); $o['bytes'] = strlen((string)$b); if ($b && ($im = @imagecreatefromstring((string)$b))) { $t = imagecreatetruecolor(512, (int)round(imagesy($im) * 512 / imagesx($im))); imagecopyresampled($t, $im, 0, 0, 0, 0, 512, imagesy($t), imagesx($im), imagesy($im)); ob_start(); imagejpeg($t, null, 78); $o['thumb'] = base64_encode((string)ob_get_clean()); $o['dim'] = imagesx($im) . 'x' . imagesy($im); } } }
  else ai_http('DELETE', PHOTO_HORDE . '/generate/status/' . $id, photo_hd(), null, 5);
}
$o['trace'] = array_slice($trace, 0, 8) + (count($trace) > 8 ? [8 => '…' . end($trace)] : []);
$o['pad'] = str_repeat('x', 80000);
echo json_encode($o, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
