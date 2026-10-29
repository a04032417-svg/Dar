#!/bin/sh
D=/home/u791571885/dgv39; mkdir -p $D; cd $D || exit 1
[ -f out.txt ] && { cat out.txt; exit 0; }
[ -f started ] && { echo "still running"; exit 0; }
: > started
curl -fsSL -o vz39.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vz39.php"
/opt/alt/php83/usr/bin/php vz39.php > out.tmp 2>&1; mv out.tmp out.txt; cat out.txt
