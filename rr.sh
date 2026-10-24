#!/bin/sh
# Dar 3.33.17 on the host, one run: check, then install (each step once; later runs show the saved output). The key is given on the command line only.
S=/home/u791571885/ds33317; mkdir -p $S; cd $S || exit 1
[ -f all.out ] && { cat all.out; exit 0; }
[ -f run.lock2 ] && { echo "running"; exit 0; }; : > run.lock2
curl -fsSL -o i.sh "https://raw.githubusercontent.com/a04032417-svg/Dar/eddb7146b09aa0cb84d9ce2c0eef200e07029175/run.sh" || exit 1
{ sh i.sh check; sh i.sh install "$1" 8b1812e661dabd806e892822aafce740120776769dcac68e17e7417c776396d7; } > all.tmp 2>&1; mv all.tmp all.out; cat all.out
