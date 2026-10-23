<?php
// Read only: the live site's appearance settings (the platform's theme: motion, colours, default theme) and who changed them lately.
$D = '/home/u791571885/domains/dar.ink/dar-data'; $p = new PDO('sqlite:' . $D . '/dar.sqlite');
$P = json_decode((string)$p->query("SELECT v FROM kv WHERE ns = 'platform' AND k = 'platform'")->fetchColumn(), true);
$out = ['theme' => $P['theme'] ?? null, 'brand' => $P['brand'] ?? null];
$a = json_decode((string)$p->query("SELECT v FROM kv WHERE ns = 'platform' AND k = 'audit'")->fetchColumn(), true);
$out['theme_audit'] = array_slice(array_values(array_filter(is_array($a) ? $a : [], fn($x) => is_array($x) && preg_match('/theme|motion|appearance/i', json_encode($x)))), 0, 8);
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n";
