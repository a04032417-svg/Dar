#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Turn a spec written by Dar's AI teams (compact DSL form, see factory/AI_SPEC.md) into an engine spec,
checking every reference on the way. Problems that can be repaired safely are repaired and reported;
anything else is reported so the 6-hour finishing session can fix it.

    python3 from_ai.py AI.json OUT.json   -> prints {"ok":..., "fixed":[...], "problems":[...]}
"""
import json, re, sys, os
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from dsl import B, F, K, M, LST, CALC, TB, CHK, FORM, H, KV, P, SIGN

TOK = re.compile(r'\{(@?#?)(\w+)(?:\.(\w+))?(?:\|(\w+))?\}')


def bi(x):
    if isinstance(x, dict):
        return x
    if isinstance(x, (list, tuple)) and len(x) >= 2:
        return B(x[0], x[1])
    return B(str(x), str(x))


def convert(ai):
    fixed, problems = [], []
    spec = {'id': re.sub(r'[^a-z0-9-]', '-', ai['id'].lower())[:40], 'title': bi(ai['title']), 'subtitle': bi(ai.get('subtitle', ai['title'])),
            'theme': ai.get('theme', {}), 'groups': {k: bi(v) for k, v in ai.get('groups', {}).items()}, 'lists': {}}
    for k, v in ai.get('lists', {}).items():
        if isinstance(v, dict):
            spec['lists'][k] = v
        else:
            v = list(v) + [None] * 5
            en_items, ar_items = v[2].split(';'), v[3].split(';')
            if len(en_items) != len(ar_items):
                n = min(len(en_items), len(ar_items)); fixed.append('list %s: trimmed to %d items (en/ar counts differed)' % (k, n))
                en_items, ar_items = en_items[:n], ar_items[:n]
            spec['lists'][k] = LST(v[0], v[1], ';'.join(en_items), ';'.join(ar_items), v[4] if v[4] and len(v[4].split(';')) >= len(en_items) else None)
    mods = []
    for m in ai.get('modules', []):
        try:
            mm = M(m['id'], m.get('g'), m['en'], m['ar'], m.get('den', ''), m.get('dar', ''), m.get('prefix', m['id'][:3].upper()), int(m.get('rows', 30)),
                   m['fields'], m.get('kpis', []), m.get('date'), m.get('val'), m.get('agg'), m.get('by', ''), m.get('heat'))
            mods.append(mm)
        except Exception as e:
            problems.append('module %s unreadable: %s' % (m.get('id'), e))
    ids = {m['id'] for m in mods}
    # reference checks per module
    for m in mods:
        keys = [f['k'] for f in m['fields']]
        if not m['fields'] or m['fields'][0].get('t') != 'id':
            m['fields'].insert(0, F('id|ID|الرقم|id')); fixed.append('%s: added ID column' % m['id']); keys.insert(0, 'id')
        seen = set()
        for f in list(m['fields']):
            if f['k'] in seen:
                m['fields'].remove(f); fixed.append('%s: removed duplicate field %s' % (m['id'], f['k'])); continue
            seen.add(f['k'])
            if f.get('t') == 'list' and f['l'] not in spec['lists']:
                f['t'] = 'text'; f['s'] = 'note'; f.pop('l'); fixed.append('%s.%s: unknown list -> text' % (m['id'], f['k']))
            if f.get('t') == 'ref' and f['m'] not in ids:
                f['t'] = 'text'; f['s'] = 'code'; f.pop('m'); fixed.append('%s.%s: unknown module ref -> text' % (m['id'], f['k']))
            if f.get('t') == 'calc':
                bad = []
                for mo in TOK.finditer(f['f']):
                    pre, a, b, c = mo.groups()
                    if pre == '' and b is None and a not in keys:
                        bad.append(a)
                    elif pre in ('@', '#') and (a not in ids or b not in [x['k'] for x in next(mm for mm in mods if mm['id'] == a)['fields']] if a in ids else True):
                        bad.append(mo.group(0))
                for mo in re.finditer(r'\{L:(\w+):(\d+)\}', f['f']):
                    if mo.group(1) not in spec['lists'] or int(mo.group(2)) >= len(spec['lists'][mo.group(1)]['items']['en']):
                        bad.append(mo.group(0))
                if bad:
                    m['fields'].remove(f); fixed.append('%s.%s: removed calc with unknown references %s' % (m['id'], f['k'], bad))
            if f.get('rel') and f['rel'] not in keys[:keys.index(f['k'])]:
                f.pop('rel'); fixed.append('%s.%s: dropped relative date' % (m['id'], f['k']))
            if f.get('eq') and f['eq'] not in keys:
                f.pop('eq')
            if f.get('per') and f['per'][0] not in keys:
                f.pop('per')
        keys = [f['k'] for f in m['fields']]
        for k in list(m.get('kpis', [])):
            w_ok = all(w[0] in keys for w in k.get('w', []))
            if (k.get('f') and k['f'] not in keys) or (k.get('f2') and k['f2'] not in keys) or not w_ok:
                m['kpis'].remove(k); fixed.append('%s: removed KPI with unknown field' % m['id'])
        rep = m.get('rep')
        if rep:
            if rep.get('date') and rep['date'] not in keys: rep.pop('date')
            if rep.get('val') and rep['val'] not in keys: rep.pop('val'); rep.pop('agg', None)
            rep['by'] = [b for b in rep.get('by', []) if b in keys and next(f for f in m['fields'] if f['k'] == b).get('t') == 'list']
            if rep.get('heat') and (rep['heat']['rows'] not in keys or rep['heat']['val'] not in keys or not rep.get('date')): rep.pop('heat')
            if not rep.get('date') and not rep.get('by'):
                m.pop('rep')
    spec['modules'] = mods
    spec['calculators'] = []
    for c in ai.get('calculators', []):
        try:
            tb = c.get('table')
            t = TB(tb['title'][0], tb['title'][1], int(tb.get('rows', 24)), tb['cols'], tb.get('while'), tuple(tb['chart']) if tb.get('chart') else None) if tb else None
            spec['calculators'].append(CALC(c['id'], c.get('g'), c['en'], c['ar'], c.get('den', ''), c.get('dar', ''), c['inputs'], c['outputs'], t))
        except Exception as e:
            problems.append('calculator %s unreadable: %s' % (c.get('id'), e))
    spec['checklists'] = []
    for c in ai.get('checklists', []):
        try:
            spec['checklists'].append(CHK(c['id'], c.get('g'), c['en'], c['ar'], c.get('den', ''), c.get('dar', ''), c['steps']))
        except Exception as e:
            problems.append('checklist %s unreadable: %s' % (c.get('id'), e))
    spec['forms'] = []
    for fm in ai.get('forms', []):
        if fm.get('module') not in ids:
            problems.append('form %s: unknown module' % fm.get('id')); continue
        keys = [f['k'] for f in next(m for m in mods if m['id'] == fm['module'])['fields']]
        blocks = []
        for b in fm.get('blocks', []):
            kind = b[0]
            if kind == 'h': blocks.append(H(b[1], b[2]))
            elif kind == 'kv': blocks.append(KV(*[k for k in b[1:] if k in keys]))
            elif kind == 'p':
                en, ar = b[1], b[2]
                for k in set(re.findall(r'\{(\w+)\}', en + ar)):
                    if k not in keys:
                        en, ar = en.replace('{%s}' % k, ''), ar.replace('{%s}' % k, '')
                blocks.append(P(en, ar))
            elif kind == 'sign': blocks.append(SIGN(*b[1:]))
        spec['forms'].append(FORM(fm['id'], fm.get('g'), fm['en'], fm['ar'], fm['module'], blocks))
    d = ai.get('dashboard', {})
    kp = [k for k in d.get('kpis', []) if k[0] in ids and k[1] < len(next(m for m in mods if m['id'] == k[0])['kpis'])]
    if not kp:
        kp = [[m['id'], i] for m in mods for i in range(min(1, len(m['kpis'])))][:8]
        fixed.append('dashboard KPIs rebuilt')
    ch = []
    for c in d.get('charts', []):
        m = next((x for x in mods if x['id'] == c.get('m')), None)
        if not m: continue
        keys = {f['k']: f for f in m['fields']}
        if c.get('by') == 'month' and not m.get('rep', {}).get('date'): continue
        if c.get('by') != 'month' and keys.get(c.get('by'), {}).get('t') != 'list': continue
        if c.get('v') and c['v'] not in keys: c.pop('v')
        if 'n' in c: c['n'] = bi(c['n'])
        ch.append(c)
    spec['dashboard'] = {'kpis': kp[:8], 'charts': ch[:6]}
    spec['store'] = ai.get('store', {})
    if len(mods) < 6: problems.append('only %d modules' % len(mods))
    return spec, fixed, problems


if __name__ == '__main__':
    ai = json.load(open(sys.argv[1], encoding='utf-8'))
    spec, fixed, problems = convert(ai)
    json.dump(spec, open(sys.argv[2], 'w', encoding='utf-8'), ensure_ascii=False, indent=0)
    print(json.dumps({'ok': not problems, 'fixed': fixed, 'problems': problems}, ensure_ascii=False))
