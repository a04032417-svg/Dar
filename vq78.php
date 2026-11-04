<?php
// Probe: visits by day and path (counts only)
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = [];
$o['days'] = q("SELECT day, kind, COUNT(*) n, COUNT(DISTINCT vid) v, SUM(bot) b FROM la_events WHERE at > ? GROUP BY day, kind ORDER BY day", [now_ms() - 7 * 86400000])->fetchAll();
$o['paths_today'] = q("SELECT path, COUNT(*) n FROM la_events WHERE day >= ? AND kind = 'view' AND bot = 0 GROUP BY path ORDER BY n DESC LIMIT 25", [gmdate('Y-m-d', time() - 86400)])->fetchAll();
$o['free'] = q("SELECT day, path, COUNT(*) n FROM la_events WHERE path LIKE '/free%' GROUP BY day, path ORDER BY day")->fetchAll();
$o['refs'] = q("SELECT ref, COUNT(*) n FROM la_events WHERE at > ? AND ref <> '' GROUP BY ref ORDER BY n DESC LIMIT 15", [now_ms() - 7 * 86400000])->fetchAll();
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
