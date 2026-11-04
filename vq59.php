<?php
// read-only: what the outreach brought since 02:50 UTC — new accounts, payments, mail sent, and the replies in Dar's own mailbox (read with PEEK: nothing is marked read)
$R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$cut = strtotime('2026-11-04T02:50:00Z') * 1000;
$o = ['v' => SYNC_VERSION, 'now' => gmdate('c')];
foreach (q("SELECT app, status, COUNT(*) n FROM tenants WHERE created > ? GROUP BY app, status", [$cut])->fetchAll() as $r) $o['new'][] = [$r['app'], $r['status'], (int)$r['n']];
$o['payments_all'] = (int)(one("SELECT COUNT(*) n FROM payments")['n'] ?? 0);
foreach (q("SELECT kind, ok, COUNT(*) n FROM mail_log WHERE at > ? GROUP BY kind, ok", [$cut])->fetchAll() as $r) $o['mail'][] = [$r['kind'], (int)$r['ok'], (int)$r['n']];
foreach (q("SELECT t.app, t.status, t.rec, t.created, u.email FROM tenants t LEFT JOIN users u ON u.tenant = t.id AND u.role = 'owner' WHERE t.created > ? ORDER BY t.created DESC LIMIT 20", [$cut])->fetchAll() as $r) { $T = json_decode((string)$r['rec']) ?: new stdClass(); $o['who'][] = [$r['app'], $r['status'], $T->plan ?? '', $T->country ?? '', $r['email'], gmdate('H:i', (int)($r['created'] / 1000))]; }
// the web server's own logs, if the host keeps them where the account can read them
foreach (array_merge(glob('/home/u791571885/domains/dar.ink/logs/*') ?: [], glob('/home/u791571885/logs/*') ?: []) as $f) {
  $e = ['f' => $f, 'kb' => (int)(filesize($f) / 1024)];
  if (is_file($f) && substr($f, -3) !== '.gz' && filesize($f) < 300000000) { $h = fopen($f, 'r'); $c = ['ex_app' => 0, 'ex_b' => 0, 'partner' => 0, 'home' => 0, 'signup_post' => 0];
    while (($l = fgets($h)) !== false) { if (strpos($l, '04/Nov/2026') === false || preg_match('/bot|crawl|spider|slurp|curl|python|wget|preview|monitor/i', $l)) continue;
      if (strpos($l, '/app/?example=1') !== false) $c['ex_app']++; if (strpos($l, '/builders/?example=1') !== false) $c['ex_b']++; if (strpos($l, 'GET /partner/') !== false) $c['partner']++; if (preg_match('#"GET / HTTP#', $l)) $c['home']++; if (strpos($l, 'POST /dar-sync.php') !== false) $c['signup_post']++; }
    fclose($h); $e['today'] = $c; }
  $o['logs'][] = $e;
}
// Dar's mailbox (IMAP, read-only EXAMINE + BODY.PEEK)
$m = mail_cfg(); $user = (string)$m['smtp_user']; $pass = (string)vault_get('smtp_password');
$host = str_replace('smtp.', 'imap.', (string)$m['smtp_host']) ?: 'imap.hostinger.com';
$fp = @stream_socket_client('ssl://' . $host . ':993', $en, $es, 15); $o['imap'] = ['host' => $host, 'user' => $user];
if (!$fp) $o['imap']['err'] = $es; else {
  stream_set_timeout($fp, 20); fgets($fp); $n = 0;
  $cmd = function ($c) use ($fp, &$n) { $t = 'a' . (++$n); fwrite($fp, "$t $c\r\n"); $out = ''; while (($l = fgets($fp)) !== false) { $out .= $l; if (strpos($l, "$t ") === 0) break; } return $out; };
  $q = fn($s) => '"' . addcslashes($s, "\"\\") . '"';
  $lg = $cmd('LOGIN ' . $q($user) . ' ' . $q($pass)); $o['imap']['login'] = preg_match('/^a1 OK/m', $lg) ? 'ok' : trim(substr($lg, 0, 120));
  if ($o['imap']['login'] === 'ok') {
    $ex = $cmd('EXAMINE INBOX'); preg_match('/(\d+) EXISTS/', $ex, $mm); $o['imap']['exists'] = (int)($mm[1] ?? 0);
    $s = $cmd('UID SEARCH SINCE 03-Nov-2026'); preg_match('/\* SEARCH([\d ]*)/', $s, $mm); $uids = array_slice(array_filter(explode(' ', trim($mm[1] ?? ''))), -120);
    foreach ($uids as $u) { $r = $cmd("UID FETCH $u (BODY.PEEK[HEADER.FIELDS (FROM SUBJECT DATE)] BODY.PEEK[1]<0.1500>)");
      preg_match('/^From:\s*(.+)$/mi', $r, $f); preg_match('/^Subject:\s*(.+)$/mi', $r, $sj); preg_match('/^Date:\s*(.+)$/mi', $r, $dt);
      $parts = preg_split('/BODY\[1\]<0> \{\d+\}\r\n/', $r); $b = $parts[1] ?? '';
      $b = preg_replace('/\)\r\na\d+ OK.*$/s', '', $b); $raw = trim($b);
      $dec = preg_match('/^[A-Za-z0-9+\/=\r\n]+$/', $raw) ? (base64_decode(preg_replace('/\s+/', '', $raw)) ?: $raw) : quoted_printable_decode($raw);
      $dec = trim(preg_replace('/\s+/u', ' ', strip_tags(mb_convert_encoding($dec, 'UTF-8', 'UTF-8, ISO-8859-1'))));
      $o['inbox'][] = ['from' => trim(iconv_mime_decode($f[1] ?? '', 2, 'UTF-8') ?: ($f[1] ?? '')), 'sub' => mb_substr(trim(iconv_mime_decode($sj[1] ?? '', 2, 'UTF-8') ?: ($sj[1] ?? '')), 0, 120), 'at' => trim($dt[1] ?? ''), 'text' => mb_substr($dec, 0, 380)]; }
  }
  $cmd('LOGOUT'); fclose($fp);
}
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
