#!/bin/sh
D=/home/u791571885/dgs57; mkdir -p $D; cd $D || exit 1
[ -f out.txt ] && { cat out.txt; exit 0; }
[ -f started ] && { echo running; exit 0; }
: > started
curl -fsSL -o vs57.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs57.php"
curl -fsSL -o leads57.txt "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/leads57.txt"
/opt/alt/php83/usr/bin/php vs57.php > out.tmp 2>&1; mv out.tmp out.txt; cat out.txt
