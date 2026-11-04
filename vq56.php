<?php
// read-only: the growth engine on the live site — is it on, what it published, who came, and which AI it writes with (names only)
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php'; @require_once $R . '/dar-grow.php'; @require_once $R . '/dar-ai.php';
$o = ['v' => SYNC_VERSION];
try { $g = grow_cfg(); unset($g['auds_all']); $o['cfg'] = $g; grow_db();
  foreach (q("SELECT lang, kind, status, COUNT(*) n FROM grow_pages GROUP BY lang, kind, status")->fetchAll() as $r) $o['pages'][] = array_values($r);
  $d = one("SELECT SUM(views) v, SUM(bots) b, SUM(se) s FROM grow_daily WHERE day >= ?", [gmdate('Y-m-d', time() - 30 * 86400)]); $o['30d'] = $d;
  foreach (q("SELECT at, kind, text FROM grow_log ORDER BY at DESC LIMIT 8")->fetchAll() as $r) $o['log'][] = [gmdate('m-d H:i', (int)($r['at'] / 1000)), $r['kind'], mb_substr($r['text'], 0, 140)];
  $o['state'] = grow_state();
} catch (Throwable $e) { $o['grow_err'] = $e->getMessage(); }
try { $a = ai_cfg(); $o['ai'] = array_map(fn($x) => is_array($x) ? array_intersect_key($x, array_flip(['provider', 'model', 'on', 'active', 'free'])) : $x, array_intersect_key($a, array_flip(['provider', 'model', 'providers', 'order', 'on']))); } catch (Throwable $e) { $o['ai_err'] = $e->getMessage(); }
$o['tg'] = (string)(platform_rec()->grow->telegram ?? '');
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
