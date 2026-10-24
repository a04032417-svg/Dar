#!/bin/sh
# read-only: the 3.33.15 install step's state on the host
ls -la /home/u791571885/ds33315/ 2>&1
echo ---; cat /home/u791571885/ds33315/install.out 2>&1
echo ---; pgrep -af "host-install|i.sh install" 2>&1
echo ---; /opt/alt/php83/usr/bin/php -r 'require "/home/u791571885/domains/dar.ink/public_html/dar-sync.php"; echo SYNC_VERSION, "\n";' 2>&1
