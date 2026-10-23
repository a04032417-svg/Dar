<?php
// Dar — a read-only look, part two (nothing is changed): the self-check's reasons, each account's state and its latest activity, the AI calls
// of the accounts that failed, the AI providers that answer, the WhatsApp gateway, the mail. Run once by a temporary cron job, then removed.
$R = '/home/u791571885/domains/dar.ink/public_html';
require $R . '/dar-sync.php';
$o = []; $h = jdec(meta_get('health')); $o['health'] = is_object($h) ? ['at' => $h->at ?? '', 'reasons' => $h->reasons ?? []] : null;
$wk = now_ms() - 7 * 86400000;
foreach (q("SELECT id, status, rec FROM tenants ORDER BY rowid DESC LIMIT 12")->fetchAll() as $r) {
    $T = jdec((string)$r['rec']); $ns = (string)$r['id']; $cnt = [];
    foreach (['leads', 'properties', 'conversations', 'deals', 'appointments'] as $c) { $l = jdec(kv_get($ns, 'db:' . $ns . ':' . $c)); $cnt[$c] = is_array($l) ? count($l) : 0; }
    $last = one('SELECT MAX(upd) m FROM kv WHERE ns = ?', [$ns])['m'] ?? 0;
    $ses = one('SELECT MAX(s.created) m FROM sessions s JOIN users u ON u.id = s.uid WHERE u.tenant = ?', [$ns])['m'] ?? 0;
    $o['t'][] = [$ns, is_object($T) ? ($T->name ?? '') : '', is_object($T) ? ($T->app ?? '') : '', $r['status'], is_object($T) ? ($T->suspend_reason ?? '') : '', is_object($T) ? ($T->plan ?? '') : '', is_object($T) ? ($T->trial_end ?? '') : '', $last ? gmdate('c', (int)($last / 1000)) : '', $ses ? gmdate('c', (int)($ses / 1000)) : '', $cnt];
}
try { $o['ai_fail'] = q("SELECT at, fn, ns, code, provider FROM lrn_calls WHERE ok = 0 AND scope = 'tenant' AND at > ? ORDER BY at DESC LIMIT 20", [$wk])->fetchAll(); foreach ($o['ai_fail'] as &$x) $x['at'] = gmdate('c', (int)($x['at'] / 1000)); unset($x); } catch (Throwable $e) { $o['ai_fail'] = $e->getMessage(); }
try { $o['ai_tenant_7d'] = q("SELECT fn, COUNT(*) n, SUM(ok = 0) bad, ROUND(AVG(ms)) ms FROM lrn_calls WHERE scope = 'tenant' AND at > ? GROUP BY fn ORDER BY n DESC LIMIT 25", [$wk])->fetchAll(); } catch (Throwable $e) { $o['ai_tenant_7d'] = $e->getMessage(); }
try { $o['ai_ready'] = function_exists('ai_ready') ? ai_ready(ai_cfg()) : null; } catch (Throwable $e) { $o['ai_ready'] = $e->getMessage(); }
try { $g = wag_health(); $o['gw'] = is_array($g) ? array_intersect_key($g, array_flip(['ok', 'version', 'sessions', 'error', 'uptime'])) : $g; } catch (Throwable $e) { $o['gw'] = $e->getMessage(); }
try { $o['mail_24h'] = q("SELECT via, ok, COUNT(*) n FROM mail_log WHERE at > ? GROUP BY via, ok", [now_ms() - 86400000])->fetchAll(); } catch (Throwable $e) { $o['mail_24h'] = $e->getMessage(); }
try { $o['wa_events'] = q("SELECT sid, type, COUNT(*) n, MAX(at) last FROM wag_events WHERE at > ? GROUP BY sid, type ORDER BY last DESC LIMIT 15", [now_ms() - 86400000])->fetchAll(); } catch (Throwable $e) { $o['wa_events'] = $e->getMessage(); }
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
