#!/bin/sh
# after 3.33.15: the install's own output, then one live check of the pricing engine's web search (run once; later runs show the saved output)
D=/home/u791571885/dgw; P=/opt/alt/php83/usr/bin/php; mkdir -p $D; cd $D || exit 1
[ -f out.txt ] && { cat out.txt; exit 0; }
curl -fsSL -o d7.php https://raw.githubusercontent.com/a04032417-svg/Dar/$1/diag7.php
{ cat /home/u791571885/ds33315/install.out; $P d7.php; echo done; } > out.tmp 2>&1; mv out.tmp out.txt; cat out.txt
