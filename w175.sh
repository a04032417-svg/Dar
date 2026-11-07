#!/bin/sh
# Read-only export of Dar's code text files for the 3.34.0 build (no data, no place tables, no images, not dar-connect.php).
# Writes base64 parts under .dar/x175 (closed to visitors); w176 removes them. Runs once; later runs print the result.
H=/home/u791571885; W=$H/domains/dar.ink/public_html; O=$W/.dar/x175
[ -f $H/t175.done ] && { cat $H/t175.done; exit 0; }
{
  mkdir -p $O && cd $W || exit 1
  find . -type f \( -name '*.js' -o -name '*.mjs' -o -name '*.html' -o -name '*.css' -o -name '*.json' -o -name '*.php' -o -name '*.txt' -o -name '*.webmanifest' -o -name '.htaccess' -o -name '*.xml' -o -name '*.svg' -o -name '*.md' \) \
    ! -path './.dar/*' ! -path './lib/places/*' ! -path './dl/*' ! -name 'dar-connect.php' | sed 's#^\./##' | sort > $H/x175.list
  wc -l < $H/x175.list
  tar czf $H/x175.tgz -T $H/x175.list && ls -l $H/x175.tgz
  sha256sum $H/x175.tgz
  base64 -w 76 $H/x175.tgz | split -l 4000 -d -a 3 --additional-suffix=.txt - $O/p
  ls $O | wc -l; rm -f $H/x175.tgz
} > $H/t175.done 2>&1
cat $H/t175.done
