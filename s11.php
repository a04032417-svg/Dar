<?php
// One-time (2026-10-23, the owner: «استخرج المتطلبات … لتتفعل بعض الخدمات غير المفعلة»): three settings that hold no secret, saved as
// «الربط والمفاتيح» saves them, then each checked: the Search Console file name (from his Search Console), the WhatsApp gateway's
// address (the platform's own, already in config.json), the DKIM selector of the host's mail (its DNS records are there). Also the
// Google sign-in's card checked again (Google accepts the app since 14:37Z). Runs once (a cron job runs each minute).
$W = '/home/u791571885/domains/dar.ink/public_html'; chdir($W); require $W . '/dar-sync.php';
if ((string)(meta_get('rq_fill_3335') ?? '') !== '') { echo "done before\n"; exit(0); }
$oc = rq_owner_ctx(); if (!$oc) { echo "no owner\n"; exit(1); }
$out = ['at' => gmdate('c')];
foreach ([['search_console', ['code' => 'googled415b4f4ae0158c8.html']], ['wa_gateway', ['url' => 'https://wa.dar.ink']], ['email_dns', ['selector' => 'hostingermail-a']]] as [$id, $v]) {
  try { $r = rq_save($oc, ['id' => $id, 'values' => $v]); $out['save'][$id] = $r['item']['status'] ?? ($r['status'] ?? 'saved'); } catch (Throwable $e) { $out['save'][$id] = 'ERR ' . $e->getMessage(); }
}
foreach (['search_console', 'wa_gateway', 'email_dns', 'app_google'] as $id) {
  try { $r = rq_check($oc, $id, 'save'); $out['check'][$id] = ($r['ok'] ?? null) === true ? 'ok' : (($r['code'] ?? '') . ' ' . mb_substr((string)($r['msg'] ?? ''), 0, 120)); } catch (Throwable $e) { $out['check'][$id] = 'ERR ' . $e->getMessage(); }
}
$rh = []; [$code, , $body] = http_out('GET', 'https://dar.ink/googled415b4f4ae0158c8.html', [], null, 15, 4000, $rh); $out['file'] = $code . ' ' . trim(substr((string)$body, 0, 80));
meta_set('rq_fill_3335', iso());
echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "\n";
