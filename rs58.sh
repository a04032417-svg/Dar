#!/bin/sh
D=/home/u791571885/dgs58; mkdir -p $D; cd $D || exit 1
[ -f out.txt ] && { cat out.txt; exit 0; }
[ -f started ] && { echo running; exit 0; }
: > started
curl -fsSL -o vs58.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs58.php"
curl -fsSL -o leads58.txt "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/leads58.txt"
/opt/alt/php83/usr/bin/php vs58.php > out.tmp 2>&1; mv out.tmp out.txt; cat out.txt
