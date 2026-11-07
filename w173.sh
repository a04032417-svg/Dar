#!/bin/sh
# Installs Dar Templates factory: two NEW files only (dar-fx.php, .dar/fx/AI_SPEC.md); no existing Dar file is touched.
# Rollback: delete dar-fx.php and its cron job. Runs once; later runs only print the result.
H=/home/u791571885; W=$H/domains/dar.ink/public_html; P=/opt/alt/php83/usr/bin/php
[ -f $H/t173.done ] && { head -c 6000 $H/t173.done; exit 0; }
{
  B="https://raw.githubusercontent.com/a04032417-svg/Dar/$1/factory"; mkdir -p $H/fxtmp
  curl -fsSL -o $H/fxtmp/dar-fx.php "$B/server/dar-fx.php" && curl -fsSL -o $H/fxtmp/AI_SPEC.md "$B/AI_SPEC.md" || { echo "download failed"; exit 1; }
  $P -l $H/fxtmp/dar-fx.php || { echo "lint failed - nothing installed"; exit 1; }
  [ -f $W/dar-fx.php ] && cp $W/dar-fx.php $H/dar-fx.php.bak
  mkdir -p $W/.dar/fx && mv $H/fxtmp/AI_SPEC.md $W/.dar/fx/AI_SPEC.md && mv $H/fxtmp/dar-fx.php $W/dar-fx.php && chmod 644 $W/dar-fx.php
  echo "installed $(sha256sum $W/dar-fx.php | cut -c1-16)"
  cd $W && $P dar-fx.php tick && $P dar-fx.php status
} > $H/t173.done 2>&1
head -c 6000 $H/t173.done
