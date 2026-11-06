#!/bin/sh
cd /home/u791571885 && [ -f t164.done ] && tail -c 9000 t164.done && exit 0
curl -fsSL -o t164.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs164.php" && /opt/alt/php83/usr/bin/php t164.php > t164.done 2>&1; tail -c 9000 t164.done
