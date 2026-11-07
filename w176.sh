#!/bin/sh
# Removes the read-only export of w175 (.dar/x175 and its list). Runs once.
H=/home/u791571885; W=$H/domains/dar.ink/public_html
[ -f $H/t176.done ] && { cat $H/t176.done; exit 0; }
{ rm -rf $W/.dar/x175 $H/x175.list $H/x175.tgz $H/w175.sh $H/t175.done; for p in $W/.dar/x175 $H/x175.list; do [ -e $p ] && echo "STILL: $p"; done; echo removed; ls $W/.dar; } > $H/t176.done 2>&1
cat $H/t176.done
