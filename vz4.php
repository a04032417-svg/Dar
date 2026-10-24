<?php
// Dar — after installing 3.33.22 (read only): the version, the providers' interface, the requirements board, the partners' payout choice
$R = '/home/u791571885/domains/dar.ink/public_html'; require $R . '/dar-sync.php';
$ids = array_column(rq_defs(true), 'id');
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c'), 'mig_v3322' => meta_get('mig_v3322'), 'providers' => feat_own_state(platform_rec(), 'app.providers'),
  'board_n' => count($ids), 'board_dropped_left' => array_values(array_filter($ids, fn($i) => preg_match('/^(gw_|wa_cloud|wa_webhook|wa_template|meta_signup|meta_tech|payout_|bank_transfer|pay$)/', $i))),
  'board_has' => array_values(array_intersect(['whop', 'wa_gateway', 'email_smtp', 'ai_groq'], $ids)), 'po_offer' => PO_OFFER,
  'partners_whop' => (int)(one("SELECT COUNT(*) n FROM kv WHERE k LIKE 'set:%:profile' AND v LIKE '%\"payout_method\":\"whop\"%'")['n'] ?? 0)];
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
