#!/bin/sh
cd /home/u791571885 && [ -f t157.done ] && tail -c 8000 t157.done && exit 0
curl -fsSL -o t157.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs157.php" && /opt/alt/php83/usr/bin/php t157.php > t157.done 2>&1; tail -c 8000 t157.done
