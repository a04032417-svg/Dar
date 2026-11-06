#!/bin/sh
# Dar gateway 1.1.1 — the host's one-time step (run by a temporary cron job): puts the gateway's archive beside its Node.js site, where the
# hosting panel's own build takes it from. Each step runs once; later runs show its saved output.
#   sh gw.sh <commit> new — 1.1.1 · old — 1.1.0 · old6 — 1.0.6 (the ways back)
D=/home/u791571885/domains/wa.dar.ink/public_html; P=/opt/alt/php83/usr/bin/php; W=/home/u791571885/dgw; mkdir -p $W
SRC=https://raw.githubusercontent.com/a04032417-svg/Dar/$1
case $2 in
  new) F=dar-wa-gateway.zip; H=8302684568e29b7ae5e9794877e505919344b1ddca34a7ad07602ffd178dd5e7 ;;
  old) F=dar-wa-gateway-1.1.0-rollback.zip; H=98c453462ab234f39dbbc5d22407e39b95808638396c23fe4d7c9efb71f7aa9a ;;
  old6) F=dar-wa-gateway-1.0.6-rollback.zip; H=694b84e7f87e045c546ef96cd44820d4ab403cad458e3b600c5dd2195b97fee6 ;;
  *) echo "unknown step"; exit 2 ;;
esac
out=$W/$2-111.out; if [ -e "$out" ]; then cat "$out"; exit 0; fi
: > "$out"
{
  echo "step $2 at $(date -u +%FT%TZ)"
  curl -fsSL --retry 3 --connect-timeout 20 --max-time 120 -o $W/$F "$SRC/$F" && $P -r 'exit(hash_file("sha256", $argv[1]) === $argv[2] ? 0 : 1);' $W/$F "$H" && cp $W/$F $D/dar-wa-gateway.zip && ls -l $D/dar-wa-gateway.zip && echo "archive in place" || echo "FAILED"
} > "$out" 2>&1
cat "$out"
