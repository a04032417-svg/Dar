<?php
// Probe: why mail is failing (counts and error text only; no addresses)
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$t = now_ms() - 86400000; $o = [];
foreach (q("SELECT kind, via, ok, substr(err,1,70) e, COUNT(*) n, MAX(at) last FROM mail_log WHERE at > ? GROUP BY kind, via, ok, e ORDER BY last DESC", [$t])->fetchAll() as $r) $o['by'][] = [$r['kind'], $r['via'], (int)$r['ok'], $r['e'], (int)$r['n'], gmdate('H:i', intdiv((int)$r['last'], 1000))];
foreach (q("SELECT at, kind, via, ok, substr(err,1,90) e FROM mail_log ORDER BY at DESC, rowid DESC LIMIT 6")->fetchAll() as $r) $o['last'][] = [gmdate('H:i', intdiv((int)$r['at'], 1000)), $r['kind'], $r['via'], (int)$r['ok'], $r['e']];
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
