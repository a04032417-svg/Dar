# -*- coding: utf-8 -*-
"""Compact, bilingual way to write engine specs by hand (Dar's AI writes the JSON form directly).

Field  : "key|English|Arabic|type|extra"
  type : id | text[:person|company|phone|email|note|code|en1;en2/ar1;ar2] | date[:past,future] | date~ref:min-max
         money:min-max[/step] | num:min-max | int:min-max | pct:min-max | list:LISTKEY | ref:MODULE | calc:FMT:=FORMULA
  extra: databar | scale | scale_r | soon | overdue | w=NN   (comma separated)
KPI    : "English|Arabic|agg|field|where|fmt"   where = "status=0,type=2" (list item index) ; agg count|sum|avg|max|min|pct|sumpct
"""
import re


def B(en, ar):
    return {'en': en, 'ar': ar}


def F(s):
    p = s.split('|')
    k, en, ar = p[0], p[1], p[2]
    rest = p[3:] or ['text']
    extra = ''
    if len(rest) > 1 and all(re.match(r'^((databar|scale|scale_r|soon|overdue|seq)|(w|eq|wts|per)=[\w:.]+)(,|$)', x) for x in rest[-1].split(',') if x):
        extra = rest.pop()
    t = '|'.join(rest)
    f = {'k': k, 'n': B(en, ar)}
    if t == 'id':
        f['t'] = 'id'
    elif t.startswith('text'):
        f['t'] = 'text'
        if ':' in t:
            v = t.split(':', 1)[1]
            if '/' in v and ';' in v:
                a, b = v.split('/', 1)
                f['s'] = {'en': a.split(';'), 'ar': b.split(';')}
            else:
                f['s'] = v
        else:
            f['s'] = 'note'
    elif t.startswith('date~'):
        ref, rng = t[5:].split(':')
        lo, hi = rng.split('-')
        f.update(t='date', rel=ref, min=int(lo), max=int(hi))
    elif t.startswith('date'):
        f['t'] = 'date'
        if ':' in t:
            a, b = t.split(':')[1].split(',')
            f.update(past=int(a), future=int(b))
    elif t.split(':')[0] in ('money', 'num', 'int', 'pct'):
        kind, rng = t.split(':', 1)
        step = None
        if '/' in rng:
            rng, step = rng.split('/')
        lo, hi = rng.split('-')
        conv = float if kind == 'pct' else (float if kind == 'num' else int)
        f.update(t=kind, min=conv(lo), max=conv(hi))
        if step:
            f['step'] = int(step)
    elif t.startswith('list:'):
        f.update(t='list', l=t[5:])
    elif t.startswith('ref:'):
        f.update(t='ref', m=t[4:])
    elif t.startswith('calc:'):
        _, fmt, fx = t.split(':', 2)
        f.update(t='calc', fmt=fmt, f=fx)
    for e in [x for x in extra.split(',') if x]:
        if e.startswith('w='):
            f['w'] = int(e[2:])
        elif e.startswith('wts='):
            f['wts'] = [int(x) for x in e[4:].split(':')]
        elif e.startswith('per='):
            f['per'] = e[4:].split(':')
        elif e.startswith('eq='):
            f['eq'] = e[3:]
        elif e == 'seq':
            f['seq'] = True
        else:
            f['cf'] = e
    return f


def K(s):
    p = (s.split('|') + [''] * 6)[:6]
    k = {'n': B(p[0], p[1]), 'a': p[2] or 'count'}
    if p[3]:
        if '/' in p[3]:
            k['f'], k['f2'] = p[3].split('/')
        else:
            k['f'] = p[3]
    if p[4]:
        k['w'] = [[a.split('=')[0], int(a.split('=')[1]) if a.split('=')[1].isdigit() else a.split('=')[1]] for a in p[4].split(',')]
    if p[5]:
        k['fmt'] = p[5]
    return k


def M(id_, g, en, ar, den, dar, prefix, rows, fields, kpis=(), date=None, val=None, agg=None, by='', heat=None):
    m = {'id': id_, 'g': g, 'n': B(en, ar), 'd': B(den, dar), 'prefix': prefix, 'rows': rows,
         'fields': [F(x) for x in fields], 'kpis': [K(x) for x in kpis]}
    rep = {}
    if date: rep['date'] = date
    if val: rep['val'] = val
    if agg: rep['agg'] = agg
    if by: rep['by'] = by.split(',')
    if heat:
        r, v, a = heat.split(':'); rep['heat'] = {'rows': r, 'val': v, 'agg': a}
    if rep:
        m['rep'] = rep
    return m


def LST(en, ar, items_en, items_ar, colors=None):
    d = {'n': B(en, ar), 'items': {'en': items_en.split(';'), 'ar': items_ar.split(';')}}
    if colors:
        d['colors'] = colors.split(';')
    return d


def CALC(id_, g, en, ar, den, dar, inputs, outputs, table=None):
    c = {'id': id_, 'g': g, 'n': B(en, ar), 'd': B(den, dar), 'inputs': [], 'outputs': []}
    for s in inputs:
        p = s.split('|')
        it = {'k': p[0], 'n': B(p[1], p[2]), 'fmt': p[3]}
        v = p[4] if len(p) > 4 else '0'
        try:
            it['v'] = float(v) if '.' in v else int(v)
        except ValueError:
            it['v'] = v
        if len(p) > 5 and p[5]:
            it['l'] = p[5]
        c['inputs'].append(it)
    for s in outputs:
        p = s.split('|')
        o = {'k': p[0], 'n': B(p[1], p[2]), 'fmt': p[3], 'f': p[4]}
        if len(p) > 5 and p[5]:
            o['hl'] = True
        c['outputs'].append(o)
    if table:
        c['table'] = table
    return c


def TB(title_en, title_ar, rows, cols, while_=None, chart=None):
    t = {'title': B(title_en, title_ar), 'rows': rows, 'cols': []}
    for s in cols:
        p = s.split('|')
        col = {'k': p[0], 'n': B(p[1], p[2]), 'fmt': p[3], 'f': p[5]}
        if p[4]:
            col['f0'] = p[4]
        t['cols'].append(col)
    if while_: t['while'] = while_
    if chart: t['chart'] = {'t': chart[0], 'cols': chart[1].split(',')}
    return t


def CHK(id_, g, en, ar, den, dar, steps):
    out = []
    for s in steps:
        p = (s.split('|') + ['', '', '', ''])[:5]
        out.append({'n': B(p[0], p[1]), 'd': int(p[2] or 0), 'o': B(p[3], p[4] or p[3])})
    return {'id': id_, 'g': g, 'n': B(en, ar), 'd': B(den, dar), 'steps': out}


def FORM(id_, g, en, ar, module, blocks):
    return {'id': id_, 'g': g, 'n': B(en, ar), 'm': module, 'blocks': blocks}


def H(en, ar): return {'t': 'h', 'x': B(en, ar)}
def KV(*keys): return {'t': 'kv', 'f': list(keys)}
def P(en, ar): return {'t': 'p', 'x': B(en, ar)}
def SIGN(*parties): return {'t': 'sign', 'x': [B(*p.split('|')) for p in parties]}
