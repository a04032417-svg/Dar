#!/bin/sh
cd /home/u791571885 && ls -la t162.done 2>&1; ps -eo pid,etime,rss,args 2>/dev/null | grep -E "php|curl" | grep -v grep | cut -c1-140 | head -12; curl -fsSL -o t163.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs163.php" && /opt/alt/php83/usr/bin/php t163.php 2>&1 | tail -c 7000
