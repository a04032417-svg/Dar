<?php
// Probe: the hub's state (counts only)
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php'; grow_db();
$o = ['cfg' => grow_cfg()];
$o['pages'] = q("SELECT status, gen, lang, COUNT(*) n FROM grow_pages GROUP BY status, gen, lang")->fetchAll();
$o['topics'] = q("SELECT status, lang, src, COUNT(*) n FROM grow_topics GROUP BY status, lang, src")->fetchAll();
$o['q'] = q("SELECT lang, kind, done, COUNT(*) n, SUM(n) hits FROM grow_q GROUP BY lang, kind, done")->fetchAll();
$o['log'] = q("SELECT at, kind, substr(text,1,110) t FROM grow_log ORDER BY at DESC LIMIT 12")->fetchAll();
foreach ($o['log'] as &$l) $l['at'] = gmdate('m-d H:i', intdiv((int)$l['at'], 1000));
$o['top7'] = q("SELECT p.slug, p.lang, SUM(d.views) v, SUM(d.se) se, SUM(d.clicks) c FROM grow_daily d JOIN grow_pages p ON p.id = d.pid WHERE d.day >= ? GROUP BY d.pid ORDER BY v DESC LIMIT 12", [gmdate('Y-m-d', time() - 7 * 86400)])->fetchAll();
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
