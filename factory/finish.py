#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Finishing line for products made by Dar's factory (runs in the 6-hour session).

    python3 finish.py FILE.b64|FILE.json [...] --out DIR
For each product: decode -> convert (repairs references) -> build Arabic + English -> recalculate in LibreOffice ->
if any cell errors, drop the faulty column / calculator / form and rebuild (max 3 rounds) -> store images ->
DIR/<id>/ with the .xlsx files, images, store.json and report.json. Prints one short line per product."""
import base64, gzip, json, os, re, shutil, sys
H = os.path.dirname(os.path.abspath(__file__))
sys.path[:0] = [os.path.join(H, 'engine'), os.path.join(H, 'specs')]
import warnings; warnings.filterwarnings('ignore')
from from_ai import convert
from build import Book
import validate, preview
from xlsxwriter.utility import xl_cell_to_rowcol


def load(p):
    t = open(p, encoding='utf-8').read()
    if p.endswith('.b64'):
        return json.loads(gzip.decompress(base64.b64decode(re.sub(r'\s+', '', t))))
    return json.loads(t)


def repair(spec, errors, pages):
    """errors like 'Sheet!E9 #VALUE!' -> remove what produced them; returns list of actions."""
    by_sheet = {p['sheet']: p for p in pages}
    acts = set()
    for e in errors:
        ref = e.split(' ')[0]; sh, cell = ref.rsplit('!', 1)
        p = by_sheet.get(sh)
        if not p:
            continue
        kind, oid = p['kind'], p.get('id')
        if kind == 'register':
            m = next(x for x in spec['modules'] if x['id'] == oid)
            r, c = xl_cell_to_rowcol(cell)
            if 1 <= c <= len(m['fields']) and m['fields'][c - 1].get('t') == 'calc':
                acts.add(('field', oid, m['fields'][c - 1]['k']))
            else:
                acts.add(('kpis', oid, None))
        elif kind == 'report':
            acts.add(('rep', oid, None))
        elif kind in ('calculator', 'form', 'checklist'):
            acts.add((kind, oid, None))
        elif kind == 'dashboard':
            acts.add(('dash', None, None))
    out = []
    for a, oid, k in acts:
        if a == 'field':
            m = next(x for x in spec['modules'] if x['id'] == oid)
            m['fields'] = [f for f in m['fields'] if f['k'] != k]
            m['kpis'] = [q for q in m.get('kpis', []) if q.get('f') != k and q.get('f2') != k]
        elif a == 'kpis':
            next(x for x in spec['modules'] if x['id'] == oid)['kpis'] = []
        elif a == 'rep':
            next(x for x in spec['modules'] if x['id'] == oid).pop('rep', None)
        elif a in ('calculator', 'form', 'checklist'):
            key = a + 's'
            spec[key] = [x for x in spec.get(key, []) if x['id'] != oid]
        elif a == 'dash':
            spec['dashboard']['charts'] = []
        out.append(('%s %s %s' % (a, oid or '', k or '')).strip())
    # dashboard KPIs must still point at existing KPIs
    spec['dashboard']['kpis'] = [k for k in spec['dashboard'].get('kpis', []) if any(m['id'] == k[0] and k[1] < len(m.get('kpis', [])) for m in spec['modules'])]
    return out


def finish(src, out):
    ai = load(src)
    spec, fixed, problems = convert(ai)
    d = os.path.join(out, spec['id']); os.makedirs(d, exist_ok=True)
    rep = {'id': spec['id'], 'fixed': fixed, 'problems': problems, 'rounds': [], 'meta': ai.get('meta', {})}
    for rnd in range(4):
        ok = True; actions = []
        for lang in ('en', 'ar'):
            man = Book(json.loads(json.dumps(spec)), lang, d).build()
            r = validate.check(os.path.join(d, man['file']))
            rep[lang] = {k: r.get(k) for k in ('ok', 'pages', 'charts', 'formulas', 'n_errors', 'dead', 'links')}
            if not r['ok']:
                ok = False
                if r['errors']:
                    actions += repair(spec, r['errors'], man['pages'])
                for sh in r['dead']:
                    actions += repair(spec, ['%s!A1 dead' % sh], man['pages'])
        rep['rounds'].append(actions)
        if ok or not actions:
            break
    json.dump(spec, open(os.path.join(d, 'spec.json'), 'w', encoding='utf-8'), ensure_ascii=False)
    rep['ok'] = all(rep.get(l, {}).get('ok') for l in ('en', 'ar')) and len(spec['modules']) >= 6
    if rep['ok']:
        for lang in ('en', 'ar'):
            preview.run(os.path.join(d, 'spec.json'), lang, os.path.join(d, 'images'))
    json.dump(ai.get('store', {}), open(os.path.join(d, 'store.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
    json.dump(rep, open(os.path.join(d, 'report.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
    print('%s %s pages=%s modules=%d repairs=%d critic=%s' % ('OK  ' if rep['ok'] else 'FAIL', spec['id'], rep.get('en', {}).get('pages'),
          len(spec['modules']), sum(len(x) for x in rep['rounds']), rep['meta'].get('scores')))
    return rep


if __name__ == '__main__':
    a = sys.argv[1:]
    out = a[a.index('--out') + 1] if '--out' in a else 'out'
    for f in [x for x in a if not x.startswith('--') and x != out]:
        try:
            finish(f, out)
        except Exception as e:
            print('FAIL %s %s' % (os.path.basename(f), e))
