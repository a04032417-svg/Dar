#!/bin/sh
# read-only diagnostics, run once (later runs show the saved output)
D=/home/u791571885/dgx; P=/opt/alt/php83/usr/bin/php; mkdir -p $D; cd $D || exit 1
[ -f out.txt ] && { cat out.txt; exit 0; }
B=https://raw.githubusercontent.com/a04032417-svg/Dar/9b8f52c871b9d463e83a6931f96f67979149c3fb
curl -fsSL -o d3.php $B/diag3.php; curl -fsSL -o d4.php $B/diag4.php
{ $P d3.php; $P d4.php; echo done; } > out.tmp 2>&1; mv out.tmp out.txt; cat out.txt
