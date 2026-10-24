#!/bin/sh
# Dar 3.33.16 on the host, one run: check, then install (each step once; later runs show the saved output)
S=/home/u791571885/ds33316; mkdir -p $S; cd $S || exit 1
[ -f all.out ] && { cat all.out; exit 0; }
curl -fsSL -o i.sh "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/run.sh" || exit 1
{ sh i.sh check; sh i.sh install "$2" "$3"; } > all.tmp 2>&1; mv all.tmp all.out; cat all.out
