#!/bin/sh
# Dar 3.33.97 — the host's one-time steps, run by a temporary cron job (each step runs once; later runs show its saved output).
#   sh run.sh check                    — reads the live site (nothing changed)
#   sh run.sh install <key> <sha256>   — the install by the site's own code (backup first; undone by itself if anything fails), then
#                                        the site's duties once
# The package's key is given on the command line only; it is never in this repository.
S=/home/u791571885/ds33397r; R=/home/u791571885/domains/dar.ink/public_html; P=/opt/alt/php83/usr/bin/php
SRC=https://raw.githubusercontent.com/a04032417-svg/Dar/8fd5d3a4b828b74c830c8949e6bbde58c301ba83
HI=1858e1142005ac3ae0fa8c1fa95075e0359b87021765ef4d46ab35058e465fa8
ZH=d93b224d29d28da34106e19bcd63be07ae14c5232ff6d7ca2a4766ac60ab2157
ENC=466c1acdf5d0e109d8a27325c615218d0848f7b1b3956a222cce40b55cf16ffe
ck() { $P -r 'exit(hash_file("sha256", $argv[1]) === $argv[2] ? 0 : 1);' "$1" "$2" || { echo "hash mismatch: $1"; return 1; }; }
get() { curl -fsSL --retry 3 --connect-timeout 20 --max-time "$3" -o "$1" "$SRC/$1" && ck "$1" "$2"; }
mkdir -p $S && cd $S || exit 1
step=${1:-}; out=$S/$step.out
case $step in check|install) ;; *) echo "unknown step"; exit 2 ;; esac
if [ -e "$out" ]; then cat "$out"; exit 0; fi
: > "$out"
{
  echo "step $step at $(date -u +%FT%TZ)"
  if [ "$step" = check ]; then
    get host-install.php "$HI" 120 && $P host-install.php audit "$R"
  else
    get host-install.php "$HI" 120 && get dar.zip.enc "$ENC" 900 && $P host-install.php install "$R" "$2" "${3:-$ZH}" && $P "$R/dar-sync.php" cron
  fi
  echo "exit $?"
} > "$out" 2>&1
cat "$out"
