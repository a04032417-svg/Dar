<?php
// A read-only look before building the market reports: the live version, the host's PHP limits, the duties' clock, and whether the
// official open-data files answer from this server (HEAD + the first bytes). Nothing changed.
ini_set('display_errors', '1'); $R = '/home/u791571885/domains/dar.ink/public_html'; chdir($R); require $R . '/dar-sync.php';
$o = ['version' => SYNC_VERSION, 'php' => PHP_VERSION, 'mem' => ini_get('memory_limit'), 'met' => ini_get('max_execution_time'), 'curl' => function_exists('curl_init'), 'gz' => function_exists('gzopen'),
  'cron_at' => gmdate('c', (int)(meta_get('cron_at') ?? 0)), 'cron_via' => meta_get('cron_via'), 'disk_free_mb' => (int)(disk_free_space($R) / 1048576), 'wa_platform' => function_exists('wag_platform_open') ? wag_platform_open() : null];
$head = function (string $u, int $bytes = 0) { $ch = curl_init($u); $h = [];
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 25, CURLOPT_USERAGENT => 'Dar/market-probe', CURLOPT_HEADERFUNCTION => function ($c, $l) use (&$h) { if (preg_match('/^(content-length|last-modified|content-type):\s*(.+)$/i', trim($l), $m)) $h[strtolower($m[1])] = trim($m[2]); return strlen($l); }]);
  if ($bytes) curl_setopt($ch, CURLOPT_RANGE, '0-' . ($bytes - 1)); else curl_setopt($ch, CURLOPT_NOBODY, true);
  $b = curl_exec($ch); $st = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); $err = curl_error($ch); curl_close($ch);
  return ['st' => $st, 'err' => $err, 'h' => $h, 'b' => $bytes ? (is_string($b) ? $b : '') : null]; };
foreach (['https://price-paid-data.publicdata.landregistry.gov.uk/pp-2026.csv', 'https://price-paid-data.publicdata.landregistry.gov.uk/pp-2025.csv', 'https://price-paid-data.publicdata.landregistry.gov.uk/pp-2024.csv',
  'https://price-paid-data.publicdata.landregistry.gov.uk/pp-monthly-update-new-version.csv', 'https://files.data.gouv.fr/geo-dvf/latest/csv/2026/departements/75.csv.gz', 'https://files.data.gouv.fr/geo-dvf/latest/csv/2025/departements/75.csv.gz',
  'https://files.data.gouv.fr/geo-dvf/latest/csv/2024/departements/01.csv.gz', 'https://files.data.gouv.fr/geo-dvf/latest/csv/2021/departements/2A.csv.gz'] as $u) { $x = $head($u); $o['head'][basename(dirname($u)) . '/' . basename($u)] = $x['st'] . ' ' . ($x['h']['content-length'] ?? '-') . ' ' . ($x['h']['last-modified'] ?? '-') . ' ' . $x['err']; }
$uk = $head('https://price-paid-data.publicdata.landregistry.gov.uk/pp-2026.csv', 900); $o['uk_first'] = explode("\n", (string)$uk['b'])[0] . ' | ' . (explode("\n", (string)$uk['b'])[1] ?? '');
$fr = $head('https://files.data.gouv.fr/geo-dvf/latest/csv/2026/departements/75.csv.gz', 400000); $raw = @gzdecode((string)$fr['b']); if ($raw === false) { $raw = ''; $t = tempnam(sys_get_temp_dir(), 'dvf'); file_put_contents($t, (string)$fr['b']); $g = gzopen($t, 'r'); for ($i = 0; $i < 4 && $g && !gzeof($g); $i++) $raw .= gzgets($g, 8000); if ($g) gzclose($g); @unlink($t); }
$L = explode("\n", $raw); $o['fr_header'] = $L[0] ?? ''; $o['fr_row'] = $L[1] ?? ''; $o['fr_lastrow_date'] = ''; 
$ls = $head('https://files.data.gouv.fr/geo-dvf/latest/csv/', 6000); $o['fr_years'] = preg_match_all('/href="(20\d\d)\//', (string)$ls['b'], $m) ? $m[1] : (string)$ls['st'];
echo json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n";
