<?php
// A read-only look: why «ربط حسابك في تيليجرام» says the gateway does not answer, and the server's latest faults. Nothing changed.
ini_set('display_errors', '0'); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$out = ['site' => SYNC_VERSION];
$x = wag_call('GET', '/v1/tg/platform'); $out['raw_tg'] = ['code' => $x['code'] ?? null, 'json' => $x['json'] ?? null, 'err' => $x['err'] ?? null, 'raw' => mb_substr((string)($x['raw'] ?? ''), 0, 300)];
$y = wag_call('GET', '/v1/sessions/platform'); $out['raw_wa'] = ['code' => $y['code'] ?? null, 'status' => $y['json']['status'] ?? null];
try { $out['tgu_state'] = tgu_state(); } catch (Throwable $e) { $out['tgu_state_err'] = get_class($e) . ': ' . $e->getMessage() . ' @' . basename($e->getFile()) . ':' . $e->getLine(); }
$out['keys'] = ['api_id' => tgu_keys()['api_id'] !== '', 'api_hash' => tgu_keys()['api_hash'] !== ''];
$f = data_dir() . '/server-errors.log'; $t = is_file($f) ? (string)file_get_contents($f) : ''; $out['errors_tail'] = mb_substr($t, -3500);
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
