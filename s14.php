<?php
// Read only: the lines of the live legal texts (assets/legal.js) that differ from the release's own copy (ref-legal.js, next to this file).
$live = file('/home/u791571885/domains/dar.ink/public_html/assets/legal.js', FILE_IGNORE_NEW_LINES); $ref = file(__DIR__ . '/ref-legal.js', FILE_IGNORE_NEW_LINES);
echo count($live), ' live / ', count($ref), " ref\n";
for ($i = 0; $i < max(count($live), count($ref)); $i++) if (($live[$i] ?? null) !== ($ref[$i] ?? null)) echo '@', $i + 1, "\nLIVE: ", $live[$i] ?? '(none)', "\n";
