#!/bin/sh
# Dar 3.33.16 on the host, one run: check, then install (each step once; later runs show the saved output). The key is given on the command line only.
S=/home/u791571885/ds33316; mkdir -p $S; cd $S || exit 1
[ -f all.out ] && { cat all.out; exit 0; }
curl -fsSL -o i.sh "https://raw.githubusercontent.com/a04032417-svg/Dar/eb8db552972cd95fa97ce56c1b3e3fdebb3ba1be/run.sh" || exit 1
{ sh i.sh check; sh i.sh install "$1" 8934efa59626f7842342413cb2be4651b95727ba9cf8ef046d963a1c82e649e4; } > all.tmp 2>&1; mv all.tmp all.out; cat all.out
