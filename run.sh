#!/bin/sh
# Dar 3.34.16 — the host's one-time steps, run by a temporary cron job (each step runs once; later runs show its saved output).
#   sh run.sh check                    — reads the live site (nothing changed)
#   sh run.sh install <key> <sha256>   — the install by the site's own code (backup first; undone by itself if anything fails), then
#                                        the site's duties once
# The package's key is given on the command line only; it is never in this repository.
S=/home/u791571885/ds33416s; R=/home/u791571885/domains/dar.ink/public_html; P=/opt/alt/php83/usr/bin/php
SRC=https://raw.githubusercontent.com/a04032417-svg/Dar/4fe56a755434b2272d01117f6fe5b7b305d2ae09
HI=1858e1142005ac3ae0fa8c1fa95075e0359b87021765ef4d46ab35058e465fa8
ZH=e4c4cf521fd1a6ed11ac0f1e993df26dcf7fa65a64eb56510c055db122886660
ENC=bfd9a91cc5e698736eea2495a39fbc708a321df21088cbae53585bb29c37a0ae
NP=9
ck() { $P -r 'exit(hash_file("sha256", $argv[1]) === $argv[2] ? 0 : 1);' "$1" "$2" || { echo "hash mismatch: $1"; return 1; }; }
get() { curl -fsSL --retry 3 --connect-timeout 20 --max-time "$3" -o "$1" "$SRC/$1" && ck "$1" "$2"; }
# the package travels in parts (the upload route takes ~700 KB at a time); joined, it must be the one hash
parts() { : > dar.zip.enc; i=1; while [ $i -le $NP ]; do curl -fsSL --retry 3 --connect-timeout 20 --max-time 300 -o "p.$i" "$SRC/dar.zip.enc.$i" || return 1; cat "p.$i" >> dar.zip.enc; rm -f "p.$i"; i=$((i+1)); done; ck dar.zip.enc "$ENC"; }
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
    get host-install.php "$HI" 120 && parts && $P host-install.php install "$R" "$2" "${3:-$ZH}" && $P "$R/dar-sync.php" cron
  fi
  echo "exit $?"
} > "$out" 2>&1
cat "$out"
