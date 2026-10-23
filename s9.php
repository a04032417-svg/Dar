<?php
// The owner's «check» for Google, run once (what his panel's button does): Google's sign-in page asked again for the platform's app at its
// return address, the answer kept, and what is «soon» by itself printed. No key is printed.
$W = '/home/u791571885/domains/dar.ink/public_html'; chdir($W); require $W . '/dar-sync.php';
$r = gg_check_all(true); $out = ['at' => gmdate('c'), 'server' => SYNC_VERSION, 'checks' => []];
foreach ($r as $x) $out['checks'][] = ['client' => $x['client'] ?? '', 'ok' => $x['ok'] ?? null, 'code' => $x['code'] ?? '', 'msg' => mb_substr((string)($x['msg'] ?? ''), 0, 160)];
$out['feat_auto'] = (object)feat_auto();
echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "\n";
