<?php
// Dar — after installing 3.33.23 (read only): the version, the yearly subscription, the subscribers' tools, the partners' bank payouts
$R = '/home/u791571885/domains/dar.ink/public_html'; require $R . '/dar-sync.php';
$parts = q("SELECT id FROM tenants WHERE app = 'partner' AND status != 'deleted'")->fetchAll(); $bank = 0; foreach ($parts as $r) { $w = function_exists('po_wb_get') ? po_wb_get((string)$r['id']) : []; if (($w['acct'] ?? '') !== '') $bank++; }
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c'), 'yearly_on' => bill_yearly_on(), 'yearly_subs' => (int)(one("SELECT COUNT(*) n FROM tenants WHERE rec LIKE '%\"cycle\":\"year\"%'")['n'] ?? 0),
  'subs_ops' => function_exists('op_subs'), 'po' => ['engine' => po_engine('iban'), 'iban_ready' => po_ready('iban'), 'offer' => PO_OFFER], 'partners' => count($parts), 'partners_bank_linked' => $bank,
  'payouts' => (function () use ($parts) { $n = []; foreach ($parts as $r) foreach (bill_rows((string)$r['id'], 'db:' . $r['id'] . ':p_payouts') as $x) { $s = (string)($x->status ?? ''); $n[$s] = ($n[$s] ?? 0) + 1; } return $n; })(),
  'world' => ['mode' => (string)(platform_rec()->world->mode ?? 'all'), 'blocked' => platform_rec()->world->blocked ?? []]];
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
