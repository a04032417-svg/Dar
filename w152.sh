#!/bin/sh
cd /home/u791571885 && curl -fsSL -o t152.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs152.php" && for s in eng talk tg; do /opt/alt/php83/usr/bin/php t152.php $s 2>&1 | tail -c 7000; done
