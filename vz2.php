<?php
// Dar — after installing 3.33.20 (reads only): the version; the subscribers' bank transfer off again; the partners' payouts (what is
// offered, which services can pay — yes/no only); the partner terms' date; what the server refused since; the open incidents.
$R = '/home/u791571885/domains/dar.ink/public_html'; require $R . '/dar-sync.php';
$tq = function (string $sql, array $a = []) { try { return q($sql, $a)->fetchAll(); } catch (Throwable $e) { return 'ERR ' . $e->getMessage(); } };
$P = platform_rec(); $b = bill_cfg($P);
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c'), 'mig3320' => meta_get('mig_v3320'), 'transfer_feature' => feat_own($P, 'pay.transfer'), 'billing_bank_switch' => $b['bank']['enabled'],
  'po' => ['offer' => PO_OFFER, 'iban_ready' => po_ready('iban'), 'whop_ready' => po_ready('whop'), 'wise_linked' => po_linked('wise')], 'terms' => BILL_PARTNER_TERMS,
  'refused' => $tq("SELECT day, app, op, code, n FROM op_refused ORDER BY n DESC LIMIT 20"),
  'incidents' => $tq("SELECT code, subject, level, status FROM doc_inc WHERE status IN ('open', 'stuck') ORDER BY rowid DESC LIMIT 12")];
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
