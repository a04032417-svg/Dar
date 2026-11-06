#!/bin/sh
# Runs the outreach script the owner ordered; the list's key comes from the cron line only.
mkdir -p /home/u791571885/w145 && cd /home/u791571885/w145 || exit 1
curl -fsSL -o q.php https://raw.githubusercontent.com/a04032417-svg/Dar/$3/vs145.php || exit 1
/opt/alt/php83/usr/bin/php q.php "${1:-watch}" "$2" d53ecf6 >> log 2>&1
tail -6 log
