<?php
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = [];
foreach (q("SELECT rec FROM tenants WHERE status = 'suspended' AND app != 'partner'")->fetchAll() as $r) { $T = json_decode((string)$r['rec']); if (!$T) continue;
  $k = ($T->app ?? '?') . '|' . ($T->suspend_reason ?? '-') . '|ro=' . (empty($T->readonly_since) ? 0 : 1) . '|sa=' . (empty($T->suspended_at) ? 0 : 1) . '|demo=' . (empty($T->demo) ? 0 : 1) . '|del=' . (is_object($T->deletion ?? null) ? 1 : 0) . '|pf=' . (empty($T->pass_for) ? 0 : 1);
  $o[$k] = ($o[$k] ?? 0) + 1; }
echo json_encode($o);
