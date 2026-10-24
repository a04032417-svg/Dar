<?php
// Dar — read-only (nothing is changed): can an account pay now (card through Whop/Dodo, bank transfer — yes/no only, no number or
// key shown), the accounts that have to pay, and examples of the interface texts the translation check refused.
$R = '/home/u791571885/domains/dar.ink/public_html'; require $R . '/dar-sync.php';
$tq = function (string $sql, array $a = []) { try { return q($sql, $a)->fetchAll(); } catch (Throwable $e) { return 'ERR ' . $e->getMessage(); } };
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
$b = bill_cfg(); $o['bank'] = ['enabled' => $b['bank']['enabled'], 'iban_set' => $b['bank']['iban'] !== '', 'iban_end' => substr($b['bank']['iban'], -4), 'beneficiary_set' => $b['bank']['beneficiary'] !== '', 'name_set' => $b['bank']['name'] !== '', 'ready' => bill_bank_ready($b)];
$P = platform_rec(); $raw = is_object($P->billing->bank ?? null) ? array_keys((array)$P->billing->bank) : null; $o['bank_raw_keys'] = $raw; $o['bank_raw_enabled'] = $P->billing->bank->enabled ?? null;
$o['provider'] = function_exists('pay_provider') ? pay_provider() : null;
if (function_exists('whop_cfg')) { $w = whop_cfg(); $o['whop'] = ['on' => $w['on'], 'on_set' => $w['on_set'], 'env' => $w['env'], 'key' => $w['key'] !== '', 'secret' => $w['secret'] !== '', 'company' => $w['company'] !== '', 'ready' => whop_ready($w), 'testers' => count($w['testers'])]; }
if (function_exists('pay_cfg')) { $d = pay_cfg(); $o['dodo'] = ['on' => $d['on'] ?? null, 'key' => ($d['key'] ?? '') !== '', 'secret' => ($d['secret'] ?? '') !== '', 'ready' => pay_ready($d)]; }
foreach (['pay.transfer', 'pay.online', 'pay.card'] as $f) $o['feat'][$f] = function_exists('feat_own') ? feat_own($P, $f) : null;
$o['feat_auto'] = function_exists('feat_auto') ? array_intersect_key(feat_auto(), array_flip(['pay.transfer', 'pay.online', 'pay.card', 'partners.payouts'])) : null;
$o['tenants'] = $tq("SELECT status st, app, COUNT(*) n FROM tenants GROUP BY status, app");
$o['payments'] = $tq("SELECT kind, status, COUNT(*) n, MAX(at) last FROM payments GROUP BY kind, status");
$o['tm_script'] = array_map(fn($r) => [$r['lang'], mb_substr((string)$r['src'], 0, 70)], $tq("SELECT lang, src FROM tm_q WHERE tries >= 3 AND err = 'script' ORDER BY RANDOM() LIMIT 30"));
$o['tm_untr'] = array_map(fn($r) => [$r['lang'], mb_substr((string)$r['src'], 0, 70)], $tq("SELECT lang, src FROM tm_q WHERE tries >= 3 AND err IN ('untranslated', 'marks') LIMIT 12"));
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
