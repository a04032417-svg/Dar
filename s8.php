<?php
// Read only: which return addresses Google's own sign-in page accepts for the platform's Google app (nobody signs in; nothing saved).
$W = '/home/u791571885/domains/dar.ink/public_html'; chdir($W); require $W . '/dar-sync.php';
$out = ['at' => gmdate('c'), 'server' => SYNC_VERSION, 'redirect_now' => oauth_redirect(), 'apps' => []];
foreach (gg_apps() as $a) $out['apps'][] = gg_short($a['client_id']) . ' ' . implode(',', $a['roles']);
$cid = (gg_apps()[0]['client_id'] ?? '');
$c = ['https://dar.ink/dar-sync.php?oauth=cb', 'https://dar.ink/oauth/cb', 'https://www.dar.ink/dar-sync.php?oauth=cb', 'https://www.dar.ink/oauth/cb',
  'https://honeydew-jackal-636006.hostingersite.com/dar-sync.php?oauth=cb', 'https://honeydew-jackal-636006.hostingersite.com/oauth/cb',
  'https://dar.ink', 'https://dar.ink/', 'https://dar.ink/app/', 'https://dar.ink/oauth/callback', 'https://dar.ink/auth/google/callback',
  'https://dar.ink/api/auth/callback/google', 'https://dar.ink/oauth2callback', 'https://dar.ink/callback', 'https://dar.ink/google/callback',
  'http://localhost', 'http://localhost:8080', 'http://localhost:3000', 'https://developers.google.com/oauthplayground'];
foreach ($c as $r) {
  $url = oauth_ep('google', 0) . '?' . http_build_query(['client_id' => $cid, 'redirect_uri' => $r, 'response_type' => 'code', 'scope' => 'openid email profile', 'state' => 'darcheck', 'prompt' => 'select_account']);
  $rh = []; [$code, $err, $body] = http_out('GET', $url, ['Accept: text/html', 'Accept-Language: en', 'User-Agent: Mozilla/5.0 (compatible; DarSignInCheck/1.0)'], null, 15, 80000, $rh);
  $loc = (string)($rh['location'] ?? '');
  if ($loc !== '' && strpos($loc, '/signin/oauth/error') !== false) { $e = gg_auth_error($loc); $v = 'REFUSED ' . $e['code']; }
  elseif ($code >= 300 && $code < 400 && $loc !== '') $v = 'ACCEPTED';
  elseif (preg_match('/Error\s+\d{3}:\s*([a-z_]{3,60})/', (string)$body, $m)) $v = 'REFUSED ' . $m[1];
  elseif ($code === 200 && preg_match('/identifier|ServiceLogin|signin\/v2/', (string)$body)) $v = 'ACCEPTED';
  else $v = 'UNCLEAR http ' . $code;
  $out['probe'][] = $v . '  ' . $r;
}
echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n";
