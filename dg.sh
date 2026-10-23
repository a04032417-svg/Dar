#!/bin/sh
# read-only diagnostics, run once (later runs show the saved output)
D=/home/u791571885/dgz; P=/opt/alt/php83/usr/bin/php; mkdir -p $D; cd $D || exit 1
[ -f out.txt ] && { cat out.txt; exit 0; }
B=https://raw.githubusercontent.com/a04032417-svg/Dar/$1
curl -fsSL -o d6.php $B/diag6.php
{ $P d6.php; echo done; } > out.tmp 2>&1; mv out.tmp out.txt; cat out.txt
