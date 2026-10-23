<?php
// One-time (2026-10-23): the platform's Google Analytics 4 measurement ID (public, not a secret — from his Analytics account, the
// «Dar» web stream), saved as «الربط والمفاتيح» saves it, then checked. Runs once.
$W = '/home/u791571885/domains/dar.ink/public_html'; chdir($W); require $W . '/dar-sync.php';
if ((string)(meta_get('rq_fill_3335b') ?? '') !== '') { echo "done before\n"; exit(0); }
$oc = rq_owner_ctx(); if (!$oc) { echo "no owner\n"; exit(1); }
$out = ['at' => gmdate('c')];
try { $r = rq_save($oc, ['id' => 'ga4', 'values' => ['id' => 'G-9QS3S00FHP']]); $out['save'] = $r['item']['status'] ?? 'saved'; } catch (Throwable $e) { $out['save'] = 'ERR ' . $e->getMessage(); }
try { $r = rq_check($oc, 'ga4', 'save'); $out['check'] = ($r['ok'] ?? null) === true ? 'ok' : (($r['code'] ?? '') . ' ' . mb_substr((string)($r['msg'] ?? ''), 0, 120)); } catch (Throwable $e) { $out['check'] = 'ERR ' . $e->getMessage(); }
$P = platform_rec(); $out['tracking'] = $P->tracking ?? null; $out['consent'] = isset($P->consent) ? 'set' : 'default';
meta_set('rq_fill_3335b', iso());
echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "\n";
