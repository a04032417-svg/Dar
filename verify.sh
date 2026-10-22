#!/bin/sh
# Dar 3.32.1 — after the install: the live site asked from outside (read only), then the temporary folders on the host removed
B=https://dar.ink
echo "hello: $(curl -sS --max-time 20 -X POST -H 'Content-Type: application/json' -H "Origin: $B" --data '{"op":"sync.hello","payload":{},"token":null,"app":"site"}' $B/dar-sync.php)"
echo "health: $(curl -sS --max-time 20 "$B/dar-sync.php?health=1" | head -c 700)"
echo "pages name: $(curl -sS --max-time 20 $B/ | grep -o 'v=3\.32\.[0-9]*-[0-9a-f]*' | head -1)"
rm -rf /home/u791571885/ds3321 /home/u791571885/ds3321b /home/u791571885/ds3321c /home/u791571885/ds3321v && echo "temporary folders removed"
ls -d /home/u791571885/ds3321* 2>/dev/null || echo "none left"
