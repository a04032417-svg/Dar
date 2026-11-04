#!/bin/sh
D=/home/u791571885/dgs65; mkdir -p $D; cd $D || exit 1
mkdir lock 2>/dev/null || { echo "already running"; exit 0; }
curl -fsSL -o vs65.php "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/vs65.php" && curl -fsSL -o leads65.txt "https://raw.githubusercontent.com/a04032417-svg/Dar/$1/leads65.txt" && /opt/alt/php83/usr/bin/php vs65.php 2>&1 | tail -3
rmdir lock; tail -5 log.txt 2>/dev/null
