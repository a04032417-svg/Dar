<?php
// Morning check (read only, counts only — no personal data): tenants, payments, Whop members, people's visits a day, free listing
// pages, AI agents' MCP calls, the MCP Registry entry, hub pages by language, the site's health
ini_set('display_errors', '0'); error_reporting(E_ALL); @set_time_limit(200);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['ver' => SYNC_VERSION, 'at' => gmdate('c')]; $since = strtotime('2026-11-04T02:50:00Z') * 1000; $day = (time() - 86400) * 1000;
$try = function ($f) { try { return $f(); } catch (Throwable $e) { return 'err: ' . substr($e->getMessage(), 0, 120); } };
$o['tenants'] = $try(fn() => ['since_0250' => (int)one('SELECT COUNT(*) n FROM tenants WHERE created >= ?', [$since])['n'], 'last24h' => (int)one('SELECT COUNT(*) n FROM tenants WHERE created >= ?', [$day])['n'], 'all' => (int)one('SELECT COUNT(*) n FROM tenants')['n'], 'by_status' => q('SELECT status, COUNT(*) n FROM tenants GROUP BY status')->fetchAll()]);
$o['payments'] = $try(fn() => ['since_0250' => q('SELECT kind, status, COUNT(*) n, SUM(amount) amt, currency FROM payments WHERE at >= ? GROUP BY kind, status, currency', [$since])->fetchAll(), 'all_paid' => (int)one("SELECT COUNT(*) n FROM payments WHERE status IN ('paid','succeeded','completed')")['n']]);
$o['pay_txns'] = $try(fn() => q('SELECT kind, status, COUNT(*) n FROM pay_txns WHERE at >= ? GROUP BY kind, status', [$since])->fetchAll());
$o['whop'] = $try(function () { $cfg = whop_cfg(); if ($cfg['key'] === '') return 'no key'; $r = whop_api('GET', '/memberships?company_id=' . rawurlencode($cfg['company']) . '&first=50', null, $cfg); $d = $r['data']['data'] ?? ($r['data'] ?? []); $rows = is_array($d) ? $d : []; $t = strtotime('2026-11-04T02:50:00Z');
    return ['http' => $r['code'] ?? ($r['status'] ?? null), 'total' => count($rows), 'since_0250' => count(array_filter($rows, fn($m) => is_array($m) && (is_numeric($m['created_at'] ?? null) ? (int)$m['created_at'] : strtotime((string)($m['created_at'] ?? ''))) >= $t)), 'by_status' => array_count_values(array_map(fn($m) => is_array($m) ? (string)($m['status'] ?? '?') : '?', $rows))]; });
$o['visits'] = $try(fn() => q("SELECT day, COUNT(*) views, COUNT(DISTINCT vid) people FROM la_events WHERE kind = 'view' AND bot = 0 AND at >= ? GROUP BY day ORDER BY day", [(time() - 7 * 86400) * 1000])->fetchAll());
$o['free_listing_pages'] = $try(fn() => ['all' => (int)one("SELECT COUNT(*) n FROM meta WHERE k LIKE 'gl:%'")['n']]);
$o['free_sites'] = $try(fn() => ['all' => (int)one("SELECT COUNT(*) n FROM meta WHERE k LIKE 'gs:%'")['n']]);
$o['mcp'] = $try(function () { $out = []; for ($i = 6; $i >= 0; $i--) { $d = gmdate('Y-m-d', time() - $i * 86400); $v = meta_get('mcp:' . $d); if ($v !== null) $out[$d] = o2a(jdec($v)); } return $out; });
$o['registry'] = $try(function () { [$c, $r] = ai_http('GET', 'https://registry.modelcontextprotocol.io/v0/servers?search=ink.dar', ['Accept: application/json'], null, 20); $j = json_decode((string)$r, true) ?: []; $s = $j['servers'] ?? []; return ['http' => $c, 'found' => array_map(fn($x) => ($x['server']['name'] ?? $x['name'] ?? '') . ' ' . ($x['server']['version'] ?? $x['version'] ?? '') . ' ' . ($x['_meta']['io.modelcontextprotocol.registry/official']['status'] ?? ''), is_array($s) ? $s : [])]; });
$o['hub'] = $try(fn() => ['by_lang' => q("SELECT lang, COUNT(*) n FROM grow_pages WHERE status = 'live' GROUP BY lang ORDER BY n DESC")->fetchAll(), 'new24h' => (int)one("SELECT COUNT(*) n FROM grow_pages WHERE status = 'live' AND created >= ?", [$day])['n']]);
$o['health'] = $try(function () { $h = health(); return ['status' => $h['status'] ?? null, 'reasons' => array_map(fn($x) => ($x['code'] ?? '') . ':' . ($x['level'] ?? ''), $h['reasons'] ?? [])]; });
$o['bank'] = $try(fn() => pbank_count());
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
