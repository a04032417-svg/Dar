#!/bin/sh
# Dar 3.33.18 on the host, one run: check, then install, then tonight's temporary files removed (each step once; later runs show the saved output). The key is given on the command line only.
S=/home/u791571885/ds33318; mkdir -p $S; cd $S || exit 1
[ -f all.out ] && { cat all.out; exit 0; }
[ -f run.lock2 ] && { echo "running"; exit 0; }; : > run.lock2
curl -fsSL -o i.sh "https://raw.githubusercontent.com/a04032417-svg/Dar/58c01a9fdf37e2eb551c417505edd5a35cbeb7be/run.sh" || exit 1
{ sh i.sh check; sh i.sh install "$1" a323b7791f11479f3d3edcc9ae5a801aef03f376e7f4029b771c2fc407ba79fc; rm -rf /home/u791571885/dgk /home/u791571885/rk.sh; } > all.tmp 2>&1; mv all.tmp all.out; cat all.out
