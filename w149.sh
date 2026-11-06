#!/bin/sh
cd /home/u791571885 && [ -f t149.done ] && cat t149.done && exit 0
curl -fsSL -o t149.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs149.php" && /opt/alt/php83/usr/bin/php t149.php > t149.done 2>&1; cat t149.done
