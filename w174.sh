#!/bin/sh
# Removes what Claude installed directly on 2026-11-07 (template factory dar-fx.php, its data dir .dar/fx, temp files).
# Restores the state before; touches nothing else. Runs once; later runs only print the result.
H=/home/u791571885; W=$H/domains/dar.ink/public_html
[ -f $H/t174.done ] && { head -c 3000 $H/t174.done; exit 0; }
{
  rm -f $W/dar-fx.php && rm -rf $W/.dar/fx && rm -rf $H/fxtmp && rm -f $H/dar-fx.php.bak
  rm -f $H/t170.php $H/t170.done $H/t171.php $H/t171.done $H/t172.php $H/t172.done $H/t173.done $H/w170.sh $H/w171.sh $H/w172.sh $H/w173.sh
  echo "removed"
  for p in $W/dar-fx.php $W/.dar/fx $H/fxtmp $H/t173.done; do [ -e "$p" ] && echo "STILL THERE: $p"; done
  ls $W/.dar
} > $H/t174.done 2>&1
head -c 3000 $H/t174.done
