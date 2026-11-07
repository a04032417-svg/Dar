<?php
// Dar Templates factory - runs 24/7 inside Dar. Each tick (cron, every minute) advances the current product by
// one AI step; when a product is finished it starts the next profession at once. Ready specs wait in .dar/fx/ready
// for the finishing session (build, recalculation test, previews, publishing), which runs every 6 hours.
//   php dar-fx.php tick | status | reset-current
// Uses Dar's own AI router (free providers with automatic fail-over); stores only files under .dar/fx.
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('display_errors', '0'); @set_time_limit(0);
$R = __DIR__; chdir($R);
$FX = $R . '/.dar/fx'; foreach (['', '/jobs', '/ready', '/failed'] as $d) @mkdir($FX . $d, 0750, true);
if (!is_file("$FX/.htaccess")) file_put_contents("$FX/.htaccess", "Require all denied\nDeny from all\n");

function fx_log(string $m): void { global $FX; file_put_contents("$FX/log.txt", gmdate('m-d H:i:s ') . $m . "\n", FILE_APPEND | LOCK_EX);
  if (filesize("$FX/log.txt") > 400000) { $t = file_get_contents("$FX/log.txt"); file_put_contents("$FX/log.txt", substr($t, -200000)); } }
function fx_j(string $p, $d = null) { return is_file($p) ? (json_decode((string)file_get_contents($p), true) ?? $d) : $d; }
function fx_w(string $p, $v): void { file_put_contents($p . '.tmp', json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)); rename($p . '.tmp', $p); }

// ---- AI: Dar's own gateway (ai_complete: free providers side by side, keys in turn, automatic fail-over) ----
function fx_ai(string $system, string $user, int $max = 6000): ?array {
  if (!function_exists('ai_complete')) { fx_log('ai_complete missing'); return null; }
  $c = ['dev' => true, 'public' => false, 'user' => ['id' => 'fx', 'name' => 'Dar Templates', 'role' => 'system'], 'trow' => null, 'tenant' => '', 'row' => []];
  $last = '';
  foreach (['standard', 'standard', 'economy'] as $tier) {
    try {
      $r = ai_complete($c, ['fn' => 'fx.factory', 'messages' => [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => $user]],
        'json' => true, 'soft_json' => true, 'tier' => $tier, 'temperature' => 0.4, 'max_tokens' => $max, 'deadline' => 50, 'cache' => false]);
    } catch (\Throwable $e) { $last = $e->getMessage(); continue; }
    $txt = (string)($r['text'] ?? $r['reply'] ?? '');
    $j = (is_array($r['json'] ?? null) ? $r['json'] : null) ?? fx_json($txt);
    if ($j) return $j;
    $last = 'no JSON: ' . mb_substr($txt, 0, 100);
  }
  fx_log('ai failed: ' . $last);
  return null;
}
function fx_json(string $t): ?array {
  $t = preg_replace('/^```(?:json)?|```$/m', '', trim($t));
  $a = strpos($t, '{'); $b = strrpos($t, '}');
  if ($a === false || $b === false) return null;
  $j = json_decode(substr($t, $a, $b - $a + 1), true);
  if (!is_array($j)) { $s = preg_replace('/,\s*([}\]])/', '$1', substr($t, $a, $b - $a + 1)); $j = json_decode((string)$s, true); }
  return is_array($j) ? $j : null;
}

const FX_SECTORS = ['Real estate', 'Healthcare clinics', 'Dental', 'Pharmacy', 'Restaurants & cafes', 'Bakery & catering', 'Retail shops', 'E-commerce', 'Construction contracting',
  'Interior design', 'Architecture', 'Engineering consultancy', 'Car rental', 'Auto repair', 'Logistics & trucking', 'Warehousing', 'Manufacturing', 'Agriculture & farms',
  'Schools', 'Training centers', 'Tutoring', 'Nurseries & kindergartens', 'Gyms & fitness', 'Beauty salons & spas', 'Barbershops', 'Hotels & short-term rentals',
  'Travel agencies', 'Event planning & weddings', 'Photography & video', 'Marketing agencies', 'Law firms', 'Accounting firms', 'HR & recruitment', 'IT services',
  'Software startups', 'Freelancers', 'Cleaning services', 'Facility management', 'Security services', 'Charities & NGOs', 'Mosques & community centers',
  'Veterinary clinics', 'Laboratories', 'Physiotherapy', 'Insurance brokers', 'Car dealerships', 'Printing', 'Tailoring & fashion', 'Jewelry', 'Furniture stores'];

const FX_SYS = "You are the head of product at Dar Templates. You design the world's most complete, specialised Excel management workbook for ONE profession at a time - far beyond competitors who sell generic sheets. Everything must fit the profession's real daily work, its real services, documents and numbers; nothing generic, nothing copied between professions. Every text a buyer sees is written in natural English AND natural Arabic. Reply with ONE JSON object only, no commentary.";

function fx_dsl(): string { static $d = null; return $d ??= (string)@file_get_contents(__DIR__ . '/.dar/fx/AI_SPEC.md'); }

// ---- the steps of one product ----
function fx_step(array &$J): bool {
  $p = $J['profession']; $who = "Profession: {$p[0]} / {$p[1]} (sector: {$J['sector']}).";
  switch ($J['stage']) {
  case 'plan':
    $done = array_slice(array_column(fx_j($GLOBALS['FX'] . '/done.json', []), 'en'), -40);
    $r = fx_ai(FX_SYS, "$who\nPlan the product. Study what the best-selling Excel templates for this profession offer and what they lack; beat them on every point.\n" .
      "Return {\"title\":[en,ar],\"subtitle\":[en,ar],\"theme\":{\"primary\":\"#hex dark\",\"accent\":\"#hex bright\"},\"competitors\":[{\"name\":\"...\",\"lacks\":\"...\"}x3],\"signature\":[8-12 services that only make sense for this profession, as [en,ar]],\"groups\":{\"g1\":[en,ar],...3-4 groups},\"modules\":[{\"id\":\"snake_case\",\"g\":\"g1\",\"en\":\"...\",\"ar\":\"...\",\"purpose\":\"...\",\"links_to\":[\"other module ids it references\"]} x 11-15],\"calculators\":[{\"id\",\"g\",\"en\",\"ar\",\"purpose\"} x 6-8],\"checklists\":[{\"id\",\"g\",\"en\",\"ar\",\"purpose\"} x 3-5],\"forms\":[{\"id\",\"g\",\"en\",\"ar\",\"module\":\"module id it prints from\",\"purpose\"} x 3-5]}\n" .
      "Recently finished professions (do not reuse their structure): " . implode(', ', $done), 5000);
    if (!$r || count($r['modules'] ?? []) < 8) return false;
    $J['plan'] = $r; $J['ai'] = ['id' => $J['id'], 'title' => $r['title'], 'subtitle' => $r['subtitle'], 'theme' => $r['theme'] ?? [], 'groups' => $r['groups'] ?? [],
      'lists' => [], 'modules' => [], 'calculators' => [], 'checklists' => [], 'forms' => []];
    $J['stage'] = 'module'; $J['i'] = 0; return true;
  case 'module':
    $plan = $J['plan']; $m = $plan['modules'][$J['i']];
    $known = []; foreach ($J['ai']['modules'] as $x) $known[] = $x['id'] . ': ' . implode(', ', array_map(fn($f) => explode('|', $f)[0] . '(' . (explode('|', $f)[3] ?? 'text') . ')', $x['fields']));
    $r = fx_ai(FX_SYS, "$who\nProduct plan modules: " . implode(', ', array_column($plan['modules'], 'id')) . "\nModules already written with their field keys:\n" . implode("\n", $known) .
      "\nLists already defined (reuse their keys): " . implode(', ', array_keys($J['ai']['lists'])) . "\n\nWrite module \"{$m['id']}\" ({$m['en']}): {$m['purpose']}. Group {$m['g']}.\n" .
      "Use 9-15 fields specific to this profession, smart calculated columns (lookups to other registers, COUNTIF/SUMIFS across registers, status logic, days left, margins), realistic sample ranges, 4 KPIs, a report (date, value, 2-3 breakdowns, heat map when meaningful).\n" .
      "Format:\n" . fx_dsl() . "\nReturn {\"module\":{...},\"lists\":{new lists only}}", 6000);
    if (!$r || empty($r['module']['fields'])) return false;
    $r['module']['id'] = $m['id']; $r['module']['g'] = $m['g'];
    $J['ai']['modules'][] = $r['module']; $J['ai']['lists'] = array_merge($J['ai']['lists'], $r['lists'] ?? []);
    if (++$J['i'] >= count($plan['modules'])) { $J['stage'] = 'calc'; $J['i'] = 0; }
    return true;
  case 'calc':
    $c = array_slice($J['plan']['calculators'], $J['i'], 2);
    $r = fx_ai(FX_SYS, "$who\nWrite these calculators: " . json_encode($c, JSON_UNESCAPED_UNICODE) . "\nEach: 4-9 inputs with realistic defaults, 4-8 outputs (highlight the main one), and a schedule table with a chart when time matters (projections, installments, payback).\n" .
      "Format:\n" . fx_dsl() . "\nReturn {\"calculators\":[...]}", 6000);
    if (!$r || empty($r['calculators'])) return false;
    foreach ($r['calculators'] as $k => $x) { $x['id'] = $c[$k]['id'] ?? $x['id']; $x['g'] = $c[$k]['g'] ?? ($x['g'] ?? null); $J['ai']['calculators'][] = $x; }
    $J['i'] += 2; if ($J['i'] >= count($J['plan']['calculators'])) { $J['stage'] = 'chk'; $J['i'] = 0; }
    return true;
  case 'chk':
    $r = fx_ai(FX_SYS, "$who\nWrite these checklists with 8-15 real steps each, owners and days from start: " . json_encode($J['plan']['checklists'], JSON_UNESCAPED_UNICODE) . "\nFormat:\n" . fx_dsl() . "\nReturn {\"checklists\":[...]}", 5000);
    if (!$r || empty($r['checklists'])) return false;
    $J['ai']['checklists'] = $r['checklists']; $J['stage'] = 'forms'; return true;
  case 'forms':
    $mods = []; foreach ($J['ai']['modules'] as $x) $mods[$x['id']] = array_map(fn($f) => explode('|', $f)[0], $x['fields']);
    $r = fx_ai(FX_SYS, "$who\nWrite these printable forms (professional wording, real clauses): " . json_encode($J['plan']['forms'], JSON_UNESCAPED_UNICODE) . "\nField keys you may use per module: " . json_encode($mods) .
      "\nFormat:\n" . fx_dsl() . "\nReturn {\"forms\":[...]}", 5000);
    if (!$r || empty($r['forms'])) return false;
    $J['ai']['forms'] = $r['forms']; $J['stage'] = 'dash'; return true;
  case 'dash':
    $mods = []; foreach ($J['ai']['modules'] as $x) $mods[] = ['id' => $x['id'], 'fields' => array_map(fn($f) => implode('|', array_slice(explode('|', $f), 0, 4)), $x['fields']), 'kpis' => $x['kpis'] ?? []];
    $r = fx_ai(FX_SYS, "$who\nChoose the 8 most important KPIs and 6 charts for the owner's dashboard.\nModules: " . json_encode($mods, JSON_UNESCAPED_UNICODE) . "\nFormat:\n" . fx_dsl() . "\nReturn {\"dashboard\":{...}}", 3000);
    if (!$r || empty($r['dashboard'])) return false;
    $J['ai']['dashboard'] = $r['dashboard']; $J['stage'] = 'critic'; return true;
  case 'critic':
    $sum = ['modules' => array_map(fn($x) => [$x['id'], $x['en'] ?? '', count($x['fields'])], $J['ai']['modules']), 'calculators' => array_column($J['ai']['calculators'], 'en'),
      'checklists' => array_column($J['ai']['checklists'], 'en'), 'forms' => array_column($J['ai']['forms'], 'en'), 'signature' => $J['plan']['signature'] ?? []];
    $r = fx_ai("You are a strict, independent reviewer of Excel products. Score honestly; reject anything generic. Reply with JSON only.",
      "$who\nProduct: " . json_encode($sum, JSON_UNESCAPED_UNICODE) . "\nCompetitors: " . json_encode($J['plan']['competitors'] ?? [], JSON_UNESCAPED_UNICODE) .
      "\nScore 0-100: specialisation to this profession (30), completeness of the profession's real services (25), depth of automation (20), beats competitors (15), clarity (10).\n" .
      "Return {\"score\":N,\"weak_modules\":[ids to rewrite],\"missing\":[{\"id\":\"snake\",\"g\":\"g1\",\"en\":..,\"ar\":..,\"purpose\":..}] (essential services missing),\"verdict\":\"...\"}", 2500);
    if (!$r) return false;
    $J['scores'][] = (int)($r['score'] ?? 0); fx_log("{$J['id']} critic " . ($r['score'] ?? '?') . ': ' . mb_substr((string)($r['verdict'] ?? ''), 0, 160));
    $add = array_slice($r['missing'] ?? [], 0, 3); $weak = array_slice($r['weak_modules'] ?? [], 0, 2);
    if (((int)($r['score'] ?? 0)) < 85 && count($J['scores']) < 3 && ($add || $weak)) {
      $J['ai']['modules'] = array_values(array_filter($J['ai']['modules'], fn($x) => !in_array($x['id'], $weak, true)));
      $keep = array_values(array_filter($J['plan']['modules'], fn($x) => !in_array($x['id'], $weak, true)));
      $J['plan']['modules'] = array_merge($keep, array_values(array_filter($J['plan']['modules'], fn($x) => in_array($x['id'], $weak, true))), $add);
      $J['i'] = count($J['ai']['modules']); $J['stage'] = 'module'; return true;
    }
    $J['stage'] = 'store'; $J['langs'] = ['en,ar', 'fr,es,de', 'pt,it,tr', 'id,ms,nl', 'ru,pl,hi', 'ur,zh,ja']; $J['i'] = 0; return true;
  case 'store':
    $L = $J['langs'][$J['i']];
    $r = fx_ai("You are a world-class conversion copywriter and SEO specialist for digital products. Reply with JSON only.",
      "$who\nProduct: " . json_encode(['title' => $J['ai']['title'], 'subtitle' => $J['ai']['subtitle'], 'signature' => $J['plan']['signature'] ?? [], 'modules' => array_column($J['ai']['modules'], 'en'),
        'calculators' => array_column($J['ai']['calculators'], 'en'), 'forms' => array_column($J['ai']['forms'], 'en')], JSON_UNESCAPED_UNICODE) .
      "\nWrite the store listing natively (not translated word for word) in these languages: $L. Use proven persuasion (clear outcome, pain then relief, specific numbers, social proof wording without fake claims, risk reversal, urgency without lies) and SEO (search terms buyers in that language really type).\n" .
      "Return {\"store\":{\"<lang>\":{\"name\":\"<=70 chars\",\"subtitle\":\"...\",\"description\":\"600-1000 words, plain text with short sections\",\"bullets\":[8],\"tags\":[13],\"seo_title\":\"<=60\",\"seo_description\":\"<=155\"}}}", 7000);
    if (!$r || empty($r['store'])) return false;
    $J['ai']['store'] = array_merge($J['ai']['store'] ?? [], $r['store']);
    if (++$J['i'] >= count($J['langs'])) $J['stage'] = 'ready';
    return true;
  }
  return false;
}

function fx_next(): array {
  global $FX;
  $st = fx_j("$FX/state.json", ['n' => 0]);
  $sector = FX_SECTORS[$st['n'] % count(FX_SECTORS)];
  $done = array_column(fx_j("$FX/done.json", []), 'en');
  $r = fx_ai(FX_SYS, "Sector: $sector. Already made: " . implode(', ', array_slice($done, -150)) .
    "\nPick ONE specific profession or business type in this sector that buyers worldwide search Excel templates for and that is not already made. Return {\"en\":\"...\",\"ar\":\"...\",\"slug\":\"kebab-case\"}", 400);
  $st['n']++; fx_w("$FX/state.json", $st);
  if (!$r || empty($r['en']) || in_array($r['en'], $done, true)) return [];
  $id = substr(preg_replace('/[^a-z0-9-]+/', '-', strtolower($r['slug'] ?? $r['en'])), 0, 40) . '-' . substr(md5($r['en']), 0, 4);
  return ['id' => $id, 'profession' => [$r['en'], $r['ar'] ?? $r['en']], 'sector' => $sector, 'stage' => 'plan', 'fails' => 0, 'scores' => [], 'started' => time()];
}

function fx_tick(): void {
  global $FX;
  $lock = fopen("$FX/lock", 'c'); if (!flock($lock, LOCK_EX | LOCK_NB)) return;     // a tick is still running
  if (fx_j("$FX/state.json", [])['paused'] ?? false) return;
  // one AI step per minute: the factory never crowds Dar's live assistants (WhatsApp, subscribers) off the shared providers
  $cur = glob("$FX/jobs/*.json")[0] ?? null;
  if (!$cur) { $J = fx_next(); if ($J) { fx_w("$FX/jobs/{$J['id']}.json", $J); fx_log("start {$J['id']} ({$J['profession'][0]})"); } return; }
  $J = fx_j($cur);
  $ok = fx_step($J);
  if (!$ok) { $J['fails']++; fx_log("{$J['id']} {$J['stage']} failed ({$J['fails']})"); } else { $J['fails'] = 0; }
  if ($J['fails'] >= 8) { rename($cur, "$FX/failed/" . basename($cur)); fx_log("{$J['id']} moved to failed"); return; }
  if (($J['stage'] ?? '') === 'ready') {
    $J['ai']['meta'] = ['profession' => $J['profession'], 'sector' => $J['sector'], 'scores' => $J['scores'], 'signature' => $J['plan']['signature'] ?? [],
      'competitors' => $J['plan']['competitors'] ?? [], 'made' => gmdate('c'), 'minutes' => intdiv(time() - $J['started'], 60)];
    fx_w("$FX/ready/{$J['id']}.json", $J['ai']);
    file_put_contents("$FX/ready/{$J['id']}.b64", chunk_split(base64_encode(gzencode(json_encode($J['ai'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 9)), 3000, "\n"));
    unlink($cur);
    $d = fx_j("$FX/done.json", []); $d[] = ['en' => $J['profession'][0], 'id' => $J['id'], 'at' => gmdate('c')]; fx_w("$FX/done.json", $d);
    fx_log("READY {$J['id']} in " . intdiv(time() - $J['started'], 60) . ' min, scores ' . implode('/', $J['scores']));
    return;
  }
  fx_w($cur, $J);
}

$cmd = $argv[1] ?? 'status';
if (PHP_SAPI === 'cli') {
  $keep = $argv; $argv = [$keep[0]]; $_SERVER['argv'] = $argv; $_SERVER['argc'] = 1;   // Dar's engine must not see our command
  require_once $R . '/dar-sync.php';                // Dar's AI gateway, keys and fail-over
  if (!function_exists('ai_complete') && is_file($R . '/dar-ai.php')) require_once $R . '/dar-ai.php';
  $argv = $keep;
  if ($cmd === 'tick') fx_tick();
  elseif ($cmd === 'reset-current') { foreach (glob("$FX/jobs/*.json") as $f) rename($f, "$FX/failed/" . basename($f)); echo "ok\n"; }
  elseif ($cmd === 'pause' || $cmd === 'resume') { $st = fx_j("$FX/state.json", ['n' => 0]); $st['paused'] = $cmd === 'pause'; fx_w("$FX/state.json", $st); echo "$cmd\n"; }
  else { echo json_encode(['ready' => count(glob("$FX/ready/*.json")), 'failed' => count(glob("$FX/failed/*.json")), 'current' => array_map(fn($f) => [basename($f), fx_j($f)['stage'] ?? '?'], glob("$FX/jobs/*.json")),
    'done' => count(fx_j("$FX/done.json", [])), 'log' => array_slice(file("$FX/log.txt") ?: [], -15)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "\n"; }
}
