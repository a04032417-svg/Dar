<?php
// Dar — read-only (nothing is changed): the mail's last deliveries (when, which way, the mailbox's answer — addresses masked), the
// mailbox settings without the password, why the texts left untranslated were refused, the self-check's open incidents.
$R = '/home/u791571885/domains/dar.ink/public_html'; require $R . '/dar-sync.php';
$tq = function (string $sql, array $a = []) { try { return q($sql, $a)->fetchAll(); } catch (Throwable $e) { return 'ERR ' . $e->getMessage(); } };
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
$o['mail_last'] = array_map(fn($r) => [gmdate('m-d H:i', (int)($r['at'] / 1000)), $r['kind'], $r['via'], (int)$r['ok'], mb_substr((string)$r['err'], 0, 90)], $tq('SELECT at, kind, via, ok, err FROM mail_log ORDER BY id DESC LIMIT 40'));
$o['mail_days'] = $tq("SELECT date(at / 1000, 'unixepoch') d, via, ok, COUNT(*) n FROM mail_log GROUP BY d, via, ok ORDER BY d DESC LIMIT 30");
$c = mail_cfg(); $o['mail_cfg'] = ['host' => $c['smtp_host'], 'port' => $c['smtp_port'], 'secure' => $c['smtp_secure'], 'user' => mask_email((string)$c['smtp_user']), 'from' => mask_email((string)$c['from_email']), 'has_pass' => vault_get('smtp_password') !== null, 'pass_len' => strlen((string)vault_get('smtp_password')), 'pass_upd' => ($u = one("SELECT upd FROM vault WHERE name = 'smtp_password'")) ? gmdate('c', (int)($u['upd'] / 1000)) : null];
$o['tm_err'] = $tq("SELECT lang, err, COUNT(*) n FROM tm_q WHERE tries >= 3 GROUP BY lang, err ORDER BY n DESC LIMIT 20");
$o['tm_lang'] = $tq("SELECT lang, done, err, last_seen, last_run FROM tm_lang ORDER BY last_seen DESC LIMIT 15");
$o['incidents'] = $tq("SELECT code, subject, layer, level, status FROM doc_inc WHERE status != 'closed' ORDER BY rowid DESC LIMIT 25");
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
