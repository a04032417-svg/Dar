#!/bin/sh
D=/home/u791571885/dgq51; mkdir -p $D; cd $D || exit 1
[ -f out.txt ] && { cat out.txt; exit 0; }
curl -fsSL -o vq51.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vq51.php"
curl -fsSL -o leads51.txt "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/leads51.txt"
/opt/alt/php83/usr/bin/php vq51.php > out.tmp 2>&1; mv out.tmp out.txt; echo done
