#!/bin/sh
# runs the check once, then removes every temporary file of tonight's checks from the host's home (later runs show the saved output)
D=/home/u791571885/dg9; mkdir -p $D; cd $D || exit 1
[ -f out.txt ] && { cat out.txt; exit 0; }
curl -fsSL -o d9.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/diag9.php"
/opt/alt/php83/usr/bin/php d9.php > out.tmp 2>&1; mv out.tmp out.txt
cd /home/u791571885 && rm -rf dgx dgy dgz dgw dg8 ds33315 ds33316 dg.php dg2.php dg3.php dg4.php dg.sh st.sh r8.sh rr.sh d7.php dg.out
cat $D/out.txt
