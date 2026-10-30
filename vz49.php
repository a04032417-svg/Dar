<?php
// Dar 3.33.46 — read-only look after the install: the tax rows of the United States and Canada, the minimum payout, and how the site's mail is signed (SPF / DKIM / DMARC records)
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php'; require_once $R . '/dar-billing.php';
$o = ['v' => SYNC_VERSION, 'min_payout' => platform_rec()->partner->min_payout ?? null, 'mig' => meta_get('mig_v33346')];
foreach (bill_platform_rows('countries') as $c) if (in_array($c->code ?? '', ['US', 'CA', 'SA'], true)) $o['tax'][] = [$c->code, $c->tax_rate ?? null];
$txt = function (string $h): array { $r = @dns_get_record($h, DNS_TXT) ?: []; return array_map(fn($x) => mb_substr((string)($x['txt'] ?? ''), 0, 110), $r); };
$o['dns'] = ['dar.ink' => $txt('dar.ink'), '_dmarc' => $txt('_dmarc.dar.ink'), 'hostingermail-a' => $txt('hostingermail-a._domainkey.dar.ink'), 'default' => $txt('default._domainkey.dar.ink')];
$o['mx'] = array_map(fn($x) => $x['target'] ?? '', @dns_get_record('dar.ink', DNS_MX) ?: []);
echo json_encode($o, JSON_UNESCAPED_UNICODE);
