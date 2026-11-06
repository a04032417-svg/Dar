<?php
// After 3.33.101: the market reports' pages sent to IndexNow once (3.33.100's own sending had the key's address wrong), and the version.
ini_set('display_errors', '1'); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['version' => SYNC_VERSION]; if (SYNC_VERSION === '3.33.101') { $o['gb'] = mr_indexnow('gb'); $o['fr'] = mr_indexnow('fr'); }
$o['ix'] = ['gb' => json_decode((string)meta_get('mr_ix:gb'), true), 'fr' => json_decode((string)meta_get('mr_ix:fr'), true)];
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR), "\n";
