#!/bin/sh
# Dar 3.34.12 — the host's one-time steps, run by a temporary cron job (each step runs once; later runs show its saved output).
#   sh run.sh check                    — reads the live site (nothing changed)
#   sh run.sh install <key> <sha256>   — the install by the site's own code (backup first; undone by itself if anything fails), then
#                                        the site's duties once
# The package's key is given on the command line only; it is never in this repository.
S=/home/u791571885/ds33412s; R=/home/u791571885/domains/dar.ink/public_html; P=/opt/alt/php83/usr/bin/php
SRC=https://raw.githubusercontent.com/a04032417-svg/Dar/8545c59b185f03ca4f34c62a36f3a077c5feecf2
HI=1858e1142005ac3ae0fa8c1fa95075e0359b87021765ef4d46ab35058e465fa8
ZH=fee4af25bfe37d98ef29a588f8b06bc0d82949a0d4d39429df1d0b7b905aa966
ENC=71ac21a66372286dce323440b327c7901c3c709a8b4174987840396633a426e6
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
