#!/bin/sh
# Read only: Dar on dar.ink as a visitor sees it after an update — each page's answer, the doors that must stay shut, the server's
# version and health, and the one-time duties of the version (the dollar prices' migration). Nothing is changed.
B=https://dar.ink; P=/opt/alt/php83/usr/bin/php; D=/home/u791571885/domains/dar.ink/dar-data
code() { curl -s -o /dev/null -m 25 -w "%{http_code}" "$B$1"; }
echo "pages:"; for u in / /developer/ /app/ /builders/ /partner/ /providers/ /console/ /pricing /legal/terms /legal/privacy /contact /status/ /library/ /hub/; do echo "  $(code "$u") $u"; done
echo "must be shut (403/404):"; for u in /book/dar-sync.php /assets/probe.php /lib/know-seed.php /lib/dar-strings.json /docs/ /Book/Old.PHP/x /.dar/ /dar-data/; do echo "  $(code "$u") $u"; done
echo "server:"; curl -s -m 25 "$B/dar-sync.php"; echo
echo "health:"; curl -s -m 40 "$B/dar-sync.php?health" | head -c 900; echo
echo "gateway: $(curl -s -o /dev/null -m 20 -w '%{http_code}' https://wa.dar.ink/)"
echo "duties:"; $P -r '$p = new PDO("sqlite:" . $argv[1] . "/dar.sqlite"); foreach (["mig_v333", "cron_cli_at", "know_seed_at", "rq_act"] as $k) { $r = $p->query("SELECT v FROM meta WHERE k = " . $p->quote($k))->fetchColumn(); echo "  $k: ", substr((string)$r, 0, 160), "\n"; }' "$D"
