<?php
// Is the WhatsApp side of the platform live, and does Dar's agent answer? States and counts only (numbers masked, no message text)
ini_set('display_errors', '0'); @set_time_limit(60);
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$mask = fn(string $p) => $p === '' ? '' : str_repeat('•', max(0, strlen($p) - 3)) . substr($p, -3);
$o = ['ver' => SYNC_VERSION, 'now' => gmdate('c')];
try { $g = function_exists('wag_cfg') ? wag_cfg() : []; $o['gw'] = ['on' => (bool)($g['on'] ?? false), 'url_set' => ($g['url'] ?? '') !== '', 'secret_set' => ($g['secret'] ?? '') !== '', 'ready' => function_exists('wag_ready') ? wag_ready() : null]; } catch (Throwable $e) { $o['gw'] = 'x:' . $e->getMessage(); }
try { $h = function_exists('wag_health') ? wag_health() : null; $o['gw_health'] = is_array($h) ? array_intersect_key($h, array_flip(['ok', 'version', 'sessions', 'uptime', 'status'])) : $h; } catch (Throwable $e) { $o['gw_health'] = 'x:' . $e->getMessage(); }
try { $r = function_exists('wag_row') ? wag_row('platform') : null; $o['gw_platform'] = $r ? ['status' => $r['status'] ?? '', 'phone' => $mask((string)($r['phone'] ?? '')), 'upd' => $r['upd'] ?? ($r['at'] ?? '')] : null; $o['gw_open'] = function_exists('wag_platform_open') ? wag_platform_open() : null; } catch (Throwable $e) { $o['gw_platform'] = 'x:' . $e->getMessage(); }
try { $o['cloud_ready'] = chan_ready('whatsapp'); $cf = chan_cfg()['whatsapp'] ?? []; $o['cloud'] = ['phone_id_set' => ($cf['phone_id'] ?? '') !== '', 'template' => ($cf['template'] ?? '') !== '']; } catch (Throwable $e) { $o['cloud'] = 'x:' . $e->getMessage(); }
try { $o['agent_all'] = function_exists('wa_agent_all') ? wa_agent_all() : null; } catch (Throwable $e) {}
try { $since = now_ms() - 7 * 86400000; $n = 0; $act = 0; $last = 0; $turns = 0; $kinds = [];
  foreach (q("SELECT k, v FROM meta WHERE k LIKE 'wa_agent:%'")->fetchAll() as $r) { $n++; $th = o2a(jdec((string)$r['v'])); $mine = 0;
    foreach (is_array($th['m'] ?? null) ? $th['m'] : [] as $m) { $at = (int)($m['at'] ?? 0); if (($m['r'] ?? '') === 'user' && ($m['by'] ?? '') === '') { $last = max($last, $at); if ($at > $since) $mine++; } if (($m['r'] ?? '') === 'assistant' && ($m['by'] ?? '') === '' && $at > $since) $turns++; }
    if ($mine) $act++; $is = (string)($th['profile']['is'] ?? ''); if ($is !== '') $kinds[$is] = ($kinds[$is] ?? 0) + 1; }
  $o['agent_threads'] = ['all' => $n, 'active_7d' => $act, 'agent_replies_7d' => $turns, 'last_in' => $last ? gmdate('m-d H:i', intdiv($last, 1000)) : '', 'kinds' => $kinds];
} catch (Throwable $e) { $o['agent_threads'] = 'x:' . $e->getMessage(); }
try { $o['wa_in_7d'] = q("SELECT status, COUNT(*) n FROM wa_in WHERE at > ? GROUP BY status", [now_ms() - 7 * 86400000])->fetchAll(); } catch (Throwable $e) { try { $o['wa_in_cols'] = array_column(q('PRAGMA table_info(wa_in)')->fetchAll(), 'name'); } catch (Throwable $e2) {} }
try { $o['ai_ready'] = function_exists('ai_ready') ? ai_ready(ai_cfg()) : null; } catch (Throwable $e) {}
try { $o['grow_ai_ok'] = function_exists('grow_ai_ok') ? grow_ai_ok() : null; $o['eleven'] = function_exists('media_eleven_key') ? media_eleven_key() !== '' : null; $o['lang_known'] = function_exists('lang_known'); $o['hear_ok'] = function_exists('media_can_hear') ? media_can_hear(ai_cfg()) : null; } catch (Throwable $e) {}
try { $o['site_strict'] = function_exists('site_strict') ? site_strict() : null; $o['isite_today'] = (int)(one("SELECT COUNT(*) n FROM meta WHERE k LIKE 'gs:%'")['n'] ?? 0); } catch (Throwable $e) {}
echo json_encode($o, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
