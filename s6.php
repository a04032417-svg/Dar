<?php
// Read only: the biggest values the platform keeps (what an owner's browser receives), and the audit list's size by entry.
$p = new PDO('sqlite:/home/u791571885/domains/dar.ink/dar-data/dar.sqlite');
foreach ($p->query("SELECT ns, k, length(v) n FROM kv ORDER BY n DESC LIMIT 25") as $r) echo $r['ns'], ' ', $r['k'], ' ', $r['n'], "\n";
echo "total platform kv: ", $p->query("SELECT SUM(length(v)) FROM kv WHERE ns = 'platform'")->fetchColumn(), "\n";
$a = json_decode((string)$p->query("SELECT v FROM kv WHERE ns = 'platform' AND k = 'audit'")->fetchColumn(), true);
if (is_array($a)) { $big = []; foreach ($a as $x) { $l = strlen(json_encode($x)); $big[] = $l; } rsort($big); echo "audit entries ", count($a), " top sizes ", implode(',', array_slice($big, 0, 10)), " sum ", array_sum($big), "\n"; }
$P = json_decode((string)$p->query("SELECT v FROM kv WHERE ns = 'platform' AND k = 'platform'")->fetchColumn(), true);
if (is_array($P)) { $s = []; foreach ($P as $k => $v) $s[$k] = strlen(json_encode($v)); arsort($s); echo "platform roots: "; foreach (array_slice($s, 0, 12, true) as $k => $n) echo "$k=$n "; echo "\n"; }
