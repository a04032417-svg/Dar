#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Dar Templates engine: one JSON spec -> one polished, working Excel workbook per language.

    python3 build.py SPEC.json --lang en --out OUTDIR [--preview]

Every page is generated from the spec, so a profession spec written by Dar's AI teams and
one written by hand go through the same code and the same quality bar.  Pages built:
Start (menu) - Dashboard (filters, KPI cards, charts) - per module: Register + Report (+ form)
- Calculators - Checklists - Printable forms - Guide - Settings (business data, all lists).
--preview limits print areas to one page per sheet so previews can be rendered.
Writes <out>/<id>-<lang>.xlsx and <out>/<id>-<lang>.manifest.json (pages, counts)."""
import json, os, re, sys, random, datetime as dt
import xlsxwriter
from xlsxwriter.utility import xl_rowcol_to_cell as rc, xl_col_to_name as cn
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from i18n import T, NAMES, COMPANIES

HDR = 7          # register: 0-based row of the table header
MAXR = 400       # register: formatted rows available for data
TODAY = dt.date.today()


def tint(h, f):
    h = h.lstrip('#'); r, g, b = int(h[0:2], 16), int(h[2:4], 16), int(h[4:6], 16)
    return '#%02X%02X%02X' % tuple(int(x + (255 - x) * f) for x in (r, g, b))


def qs(name):
    return "'" + name.replace("'", "''") + "'"


def lit(s):
    """Excel string literal, split under the 255-character limit."""
    s = str(s).replace('"', '""')
    parts = [s[i:i + 240] for i in range(0, len(s), 240)] or ['']
    return '&'.join('"%s"' % p for p in parts)


class Book:
    def __init__(self, spec, lang, out, preview=False):
        self.s, self.lang, self.preview = spec, lang, preview
        self.t = T.get(lang, T['en'])
        self.rnd = random.Random(hash(spec['id']) & 0xffffffff)
        th = spec.get('theme', {})
        self.P = th.get('primary', '#0F5E63')
        self.A = th.get('accent', '#F2A541')
        self.PAL = th.get('palette', [self.P, '#2E86AB', '#F2A541', '#E4572E', '#76B041', '#8E6C8A', '#17BEBB', '#FFC914'])
        self.font = th.get('font', 'Segoe UI')
        os.makedirs(out, exist_ok=True)
        self.path = os.path.join(out, '%s-%s.xlsx' % (spec['id'], lang))
        self.wb = xlsxwriter.Workbook(self.path, {'strings_to_numbers': False, 'strings_to_formulas': False})
        self.wb.set_properties({'title': self.L(spec['title']), 'author': 'Dar Templates', 'company': 'Dar Templates',
                                'comments': self.L(spec.get('subtitle', ''))})
        self._fmt = {}
        self.names = set()
        self.pages = []            # (sheetname, kind, title, desc)
        self.stats = {'charts': 0, 'formulas': 0, 'validations': 0}
        self.mods = {m['id']: m for m in spec.get('modules', [])}
        self.lists = spec.get('lists', {})

    # ---------- helpers ----------
    def L(self, x):
        if isinstance(x, dict):
            return x.get(self.lang) or x.get('en') or next(iter(x.values()), '')
        return '' if x is None else str(x)

    def F(self, **k):
        base = {'font_name': self.font, 'font_size': 10, 'valign': 'vcenter'}
        if self.t['rtl']:
            base['reading_order'] = 2
        base.update(k)
        key = json.dumps(base, sort_keys=True)
        if key not in self._fmt:
            self._fmt[key] = self.wb.add_format(base)
        return self._fmt[key]

    def sheetname(self, title):
        n = re.sub(r"[\[\]:*?/\\']", '', title).strip()[:31] or 'Sheet'
        base, i = n, 2
        while n.lower() in self.names:
            suf = ' %d' % i
            n = base[:31 - len(suf)] + suf; i += 1
        self.names.add(n.lower())
        return n

    def add(self, title, kind, desc='', tab=None):
        name = self.sheetname(title)
        ws = self.wb.add_worksheet(name)
        if self.t['rtl']:
            ws.right_to_left()
        ws.hide_gridlines(2)
        ws.set_zoom(90)
        if tab:
            ws.set_tab_color(tab)
        self.pages.append({'sheet': name, 'kind': kind, 'title': title, 'desc': desc})
        return ws, name

    def fml(self, ws, r, c, f, fmt=None):
        ws.write_formula(r, c, f if f.startswith('=') else '=' + f, fmt)
        self.stats['formulas'] += 1

    def banner(self, ws, title, sub, ncols, color=None):
        color = color or self.P
        ws.set_row(0, 38); ws.set_row(1, 22)
        ws.merge_range(0, 1, 0, ncols, title, self.F(bold=True, font_size=18, font_color='#FFFFFF', bg_color=color, indent=1))
        ws.merge_range(1, 1, 1, ncols, sub, self.F(italic=True, font_size=10, font_color='#FFFFFF', bg_color=tint(color, .25), indent=1))
        ws.set_column(0, 0, 2)

    def navlinks(self, ws, r, c1, c2):
        f = self.F(font_color=self.P, bold=True, underline=1)
        ws.write_url(r, c1, "internal:%s!A1" % qs(self.start_name), f, string=self.t['menu'])
        ws.write_url(r, c2, "internal:%s!A1" % qs(self.dash_name), f, string=self.t['back_dash'])

    def listitems(self, key):
        lst = self.lists[key]
        it = lst['items']
        return it.get(self.lang) or it.get('en') if isinstance(it, dict) else it

    # ---------- settings ----------
    def build_settings(self):
        t = self.t
        ws, name = self.add(t['settings'], 'settings', t['tip_set'], '#7F8C8D')
        self.set_name = name
        ws.set_column(1, 1, 22); ws.set_column(2, 2, 28); ws.set_column(3, 3, 3)
        ws.set_column(4, 5, 12)
        self.banner(ws, t['settings'], t['tip_set'], 8 + len(self.lists))
        h = self.F(bold=True, font_color='#FFFFFF', bg_color=self.P, border=1, border_color='#D0D5DA')
        inp = self.F(bg_color='#FFF8DC', border=1, border_color='#D0D5DA', locked=False)
        lab = self.F(bold=True, border=1, border_color='#D0D5DA', bg_color='#F4F6F8')
        ws.merge_range(3, 1, 3, 2, t['biz'], h)
        rows = [('biz_name', self.L(self.s.get('biz_default', t['my_business']))), ('biz_phone', ''), ('biz_email', ''),
                ('biz_addr', ''), ('currency', self.L(self.s.get('currency', {'en': 'USD', 'ar': 'ر.س'})))]
        self.SET = {}
        for i, (k, v) in enumerate(rows):
            ws.write(4 + i, 1, t[k], lab); ws.write(4 + i, 2, v, inp)
            self.SET[k] = "%s!$C$%d" % (qs(name), 5 + i)
        # years / months
        ws.write(3, 4, t['years'], h); ws.write(3, 5, t['month'], h)
        y0 = TODAY.year - 3
        for i in range(10):
            ws.write_number(4 + i, 4, y0 + i, inp)
        ws.write(4, 5, t['all'], lab)
        for i, m in enumerate(t['months']):
            ws.write(5 + i, 5, m, lab)
        self.YEARS = "=%s!$E$5:$E$14" % qs(name)
        self.MONTHS_ALL = "=%s!$F$5:$F$17" % qs(name)
        self.MONTHS = "%s!$F$6:$F$17" % qs(name)
        # lists
        ws.merge_range(2, 7, 2, 7 + max(0, len(self.lists) - 1), t['lists'], self.F(bold=True, font_color=self.P)) \
            if len(self.lists) > 1 else ws.write(2, 7, t['lists'], self.F(bold=True, font_color=self.P))
        self.LIST = {}
        for j, (key, lst) in enumerate(self.lists.items()):
            c = 7 + j
            items = self.listitems(key)
            ws.set_column(c, c, max(14, min(30, max([len(x) for x in items] + [len(self.L(lst['n']))]) + 2)))
            ws.write(3, c, self.L(lst['n']), h)
            for i, it in enumerate(items):
                ws.write(4 + i, c, it, inp)
            for i in range(len(items), len(items) + 15):
                ws.write_blank(4 + i, c, None, inp)
            col = cn(c)
            nm = 'L_' + re.sub(r'\W', '_', key)
            self.wb.define_name(nm, "=OFFSET(%s!$%s$5,0,0,MAX(1,COUNTA(%s!$%s$5:$%s$60)),1)" % (qs(name), col, qs(name), col, col))
            self.LIST[key] = {'name': nm, 'col': col, 'items': items}
        ws.write(16, 1, t['sample_note'], self.F(italic=True, font_color='#7F8C8D'))
        return ws

    # ---------- sample values ----------
    def sample(self, f, i, row):
        t, rnd = f.get('t', 'text'), self.rnd
        if t == 'date':
            if 'rel' in f and f['rel'] in row and isinstance(row[f['rel']], dt.date):
                return row[f['rel']] + dt.timedelta(days=rnd.randint(f.get('min', 1), f.get('max', 30)))
            past, fut = f.get('past', 330), f.get('future', 0)
            return TODAY + dt.timedelta(days=rnd.randint(-past, fut))
        if t in ('money', 'num', 'int'):
            lo, hi = f.get('min', 0), f.get('max', 1000)
            if t == 'num' and (hi - lo <= 20 or lo != int(lo) or hi != int(hi)):
                return round(rnd.uniform(lo, hi), 2)
            lo, hi = int(lo), int(hi)
            step = int(f.get('step', 1 if t != 'money' else max(1, (hi - lo) // 200)))
            return lo + step * rnd.randint(0, max(0, (hi - lo) // step))
        if t == 'pct':
            return round(rnd.uniform(f.get('min', 0), f.get('max', 1)), 3)
        if f.get('per') and isinstance(row.get(f['per'][0]), (int, float)):
            k_, lo_, hi_ = f['per']
            v = row[k_] * rnd.uniform(float(lo_), float(hi_))
            if t == 'pct':
                return round(v, 4)
            st = int(f.get('step', 1)) or 1
            return int(round(v / st) * st)
        if f.get('eq') and isinstance(row.get(f['eq']), (int, float)):
            base = row[f['eq']]
            v = base * rnd.choice([1, 1, 1, 1, 1, 1, .5, 0])
            return int(round(v)) if t != 'pct' else v
        if t == 'list':
            items = self.LIST[f['l']]['items']
            if f.get('seq'):
                return items[i % len(items)]
            wts = f.get('wts')
            return rnd.choices(items, weights=wts[:len(items)] if wts else None)[0]
        if t == 'ref':
            ids = self.ids.get(f['m'], [])
            return rnd.choice(ids) if ids else ''
        kind = f.get('s', 'note')
        if isinstance(kind, (list, dict)):
            opts = self.L(kind) if isinstance(kind, dict) else kind
            if isinstance(opts, str):
                opts = [opts]
            return rnd.choice(opts)
        if kind == 'person':
            return rnd.choice(NAMES.get(self.lang, NAMES['en']))
        if kind == 'company':
            return rnd.choice(COMPANIES.get(self.lang, COMPANIES['en']))
        if kind == 'phone':
            return ('05%d%07d' if self.lang == 'ar' else '+1-555-01%02d' ) % ((rnd.randint(0, 9), rnd.randint(0, 9999999)) if self.lang == 'ar' else rnd.randint(0, 99))
        if kind == 'email':
            return 'contact%02d@example.com' % (i + 1)
        if kind == 'code':
            return '%s-%04d' % (f.get('pre', 'REF'), rnd.randint(1, 9999))
        return ''

    # ---------- registers ----------
    def colmap(self, m):
        return {f['k']: 1 + j for j, f in enumerate(m['fields'])}

    def keyfield(self, m):
        for f in m['fields']:
            if f.get('t') not in ('id', 'calc'):
                return f['k']
        return m['fields'][0]['k']

    def rng(self, m, k):
        c = cn(m['_cols'][k])
        return "%s!$%s$%d:$%s$%d" % (qs(m['_sheet']), c, HDR + 2, c, HDR + 1 + MAXR)

    def resolve(self, expr, m, r):
        """{field} -> same-row cell; {@mod.field|key} -> lookup in another register."""
        def lk(mo):
            mod, fld, key = mo.group(1), mo.group(2), mo.group(3)
            om = self.mods[mod]
            return "IFERROR(INDEX(%s,MATCH(%s,%s,0)),\"\")" % (
                self.rng(om, fld), rc(r, m['_cols'][key]), self.rng(om, om['fields'][0]['k']))
        expr = re.sub(r'\{@(\w+)\.(\w+)\|(\w+)\}', lk, expr)
        expr = self.common_tokens(expr)
        expr = re.sub(r'\{(\w+)\}', lambda mo: rc(r, m['_cols'][mo.group(1)]), expr)
        return expr

    def common_tokens(self, expr):
        """{#mod.field} range of a register column, {L:list:i} list item text, {tx:English/Arabic} text, {S:key} setting."""
        expr = re.sub(r'\{#(\w+)\.(\w+)\}', lambda mo: self.rng(self.mods[mo.group(1)], mo.group(2)), expr)
        expr = re.sub(r'\{L:(\w+):(\d+)\}', lambda mo: lit(self.LIST[mo.group(1)]['items'][int(mo.group(2))]), expr)
        expr = re.sub(r'\{tx:([^}]*)\}', lambda mo: lit((mo.group(1).split('/') + [''])[1 if self.lang == 'ar' else 0]), expr)
        expr = re.sub(r'\{S:(\w+)\}', lambda mo: self.SET[mo.group(1)], expr)
        return expr

    def prepare_modules(self):
        self.ids = {}
        order = self.s.get('modules', [])
        for m in order:
            m['_cols'] = self.colmap(m)
            n = m.get('rows', 30)
            self.ids[m['id']] = ['%s-%04d' % (m.get('prefix', m['id'][:3].upper()), i + 1) for i in range(n)]

    def fmt_for(self, t, calc=False, locked=False):
        bg = '#EEF1F4' if calc else '#FFFFFF'
        base = dict(border=1, border_color='#E1E5EA', bg_color=bg)
        if not calc:
            base['locked'] = False
        nf = {'money': '#,##0', 'num': '#,##0.00', 'int': '#,##0', 'pct': '0.0%', 'date': 'yyyy-mm-dd'}.get(t)
        if nf:
            base['num_format'] = nf
            base['align'] = 'center' if t == 'date' else None
        base = {k: v for k, v in base.items() if v is not None}
        return self.F(**base)

    def build_register(self, m):
        t = self.t
        title = self.L(m['n'])
        ws, name = self.add(title, 'register', self.L(m.get('d', '')), self.P)
        assert name == m['_sheet'], (name, m['_sheet'])
        ncol = len(m['fields'])
        self.banner(ws, title, self.L(m.get('d', '')), max(ncol, 8))
        self.navlinks(ws, 2, 1, 3)
        ws.write(2, 5, t['tip_reg'], self.F(italic=True, font_color='#7F8C8D'))
        # widths
        for f in m['fields']:
            tt = f.get('t', 'text')
            w = f.get('w') or {'id': 11, 'date': 12, 'money': 14, 'num': 12, 'int': 10, 'pct': 10, 'list': 16,
                               'ref': 13, 'calc': 14}.get(tt, 20)
            if f.get('s') == 'note':
                w = f.get('w', 30)
            ws.set_column(m['_cols'][f['k']], m['_cols'][f['k']], max(w, min(28, len(self.L(f['n'])) + 3)))
        # mini KPI cards (all time)
        kp = m.get('kpis', [])[:4]
        c = 1
        for k in kp:
            span = max(1, min(2, ncol // max(1, len(kp))))
            ws.merge_range(4, c, 4, c + span - 1, self.L(k['n']), self.F(font_size=9, font_color='#5D6D7E', bg_color='#F4F6F8', align='center'))
            ws.merge_range(5, c, 5, c + span - 1, '', self.F(bold=True, font_size=14, font_color=self.P, bg_color='#F4F6F8', align='center',
                                                             num_format=self.kfmt(k)))
            self.fml(ws, 5, c, self.kpi(m, k), self.F(bold=True, font_size=14, font_color=self.P, bg_color='#F4F6F8', align='center', num_format=self.kfmt(k)))
            c += span + 1
        ws.set_row(5, 26)
        # header
        hf = self.F(bold=True, font_color='#FFFFFF', bg_color=self.P, align='center', text_wrap=True, border=1, border_color=self.P)
        ws.set_row(HDR, 30)
        cols = []
        for f in m['fields']:
            cols.append({'header': self.L(f['n'])})
        last = 1 + ncol - 1
        ws.add_table(HDR, 1, HDR + MAXR, last, {'columns': cols, 'style': 'Table Style Light 1', 'autofilter': True,
                                                 'name': 'T_' + re.sub(r'\W', '_', m['id'])})
        for f in m['fields']:
            ws.write(HDR, m['_cols'][f['k']], self.L(f['n']), hf)
        # sample rows + formulas
        key = self.keyfield(m)
        kc = rc(HDR + 1, m['_cols'][key], False, False)
        nsam = m.get('rows', 30)
        samples = []
        for i in range(nsam):
            row = {}
            for f in m['fields']:
                if f.get('t') not in ('id', 'calc'):
                    row[f['k']] = self.sample(f, i, row)
            samples.append(row)
        m['_samples'] = samples
        pre = m.get('prefix', m['id'][:3].upper())
        for i in range(MAXR):
            r = HDR + 1 + i
            for f in m['fields']:
                c = m['_cols'][f['k']]; tt = f.get('t', 'text')
                if tt == 'id':
                    if i < nsam:
                        ws.write_string(r, c, self.ids[m['id']][i], self.fmt_for('id', calc=True))
                    else:
                        self.fml(ws, r, c, 'IF(%s="","","%s-"&TEXT(ROW()-%d,"0000"))' % (rc(r, m['_cols'][key]), pre, HDR + 1),
                                 self.fmt_for('id', calc=True))
                elif tt == 'calc':
                    expr = self.resolve(f['f'].lstrip('='), m, r)
                    self.fml(ws, r, c, 'IF(%s="","",%s)' % (rc(r, m['_cols'][key]), expr), self.fmt_for(f.get('fmt', 'num'), calc=True))
                else:
                    fm = self.fmt_for(tt if tt in ('money', 'num', 'int', 'pct', 'date') else 'text')
                    v = samples[i].get(f['k']) if i < nsam else None
                    if v is None or v == '':
                        ws.write_blank(r, c, None, fm)
                    elif tt == 'date':
                        ws.write_datetime(r, c, dt.datetime.combine(v, dt.time()), fm)
                    elif isinstance(v, (int, float)):
                        ws.write_number(r, c, v, fm)
                    else:
                        ws.write_string(r, c, str(v), fm)
        # validation + conditional formats
        r1, r2 = HDR + 1, HDR + MAXR
        for f in m['fields']:
            c = m['_cols'][f['k']]; tt = f.get('t', 'text')
            if tt == 'list':
                ws.data_validation(r1, c, r2, c, {'validate': 'list', 'source': '=' + self.LIST[f['l']]['name']})
                self.stats['validations'] += 1
                cols_ = self.lists[f['l']].get('colors')
                if cols_:
                    for it, colr in zip(self.LIST[f['l']]['items'], cols_):
                        ws.conditional_format(r1, c, r2, c, {'type': 'cell', 'criteria': '==', 'value': lit(it),
                                                             'format': self.F(bg_color=tint(colr, .72), font_color=colr, bold=True)})
            elif tt == 'ref':
                om = self.mods[f['m']]
                ws.data_validation(r1, c, r2, c, {'validate': 'list', 'source': '=' + self.rng(om, om['fields'][0]['k'])})
                self.stats['validations'] += 1
            elif tt == 'date':
                ws.data_validation(r1, c, r2, c, {'validate': 'date', 'criteria': '>', 'value': dt.datetime(2000, 1, 1),
                                                  'error_message': self.L({'en': 'Enter a date', 'ar': 'أدخل تاريخاً'})})
                self.stats['validations'] += 1
            elif tt in ('money', 'num', 'int'):
                ws.data_validation(r1, c, r2, c, {'validate': 'decimal', 'criteria': '>=', 'value': f.get('vmin', 0)})
                self.stats['validations'] += 1
            cf = f.get('cf')
            if cf == 'databar':
                ws.conditional_format(r1, c, r2, c, {'type': 'data_bar', 'bar_color': tint(self.P, .35), 'bar_solid': True})
            elif cf == 'scale':
                ws.conditional_format(r1, c, r2, c, {'type': '3_color_scale', 'min_color': '#F8696B', 'mid_color': '#FFEB84', 'max_color': '#63BE7B'})
            elif cf == 'scale_r':
                ws.conditional_format(r1, c, r2, c, {'type': '3_color_scale', 'min_color': '#63BE7B', 'mid_color': '#FFEB84', 'max_color': '#F8696B'})
            elif cf in ('overdue', 'soon'):
                cell = rc(r1, c, True, False).replace('$', '', 0)
                ref = '$%s%d' % (cn(c), r1 + 1)
                ws.conditional_format(r1, c, r2, c, {'type': 'formula', 'criteria': '=AND(%s<>"",%s<TODAY())' % (ref, ref),
                                                     'format': self.F(bg_color='#FADBD8', font_color='#C0392B', bold=True)})
                ws.conditional_format(r1, c, r2, c, {'type': 'formula', 'criteria': '=AND(%s<>"",%s<=TODAY()+30)' % (ref, ref),
                                                     'format': self.F(bg_color='#FDEBD0', font_color='#B9770E')})
        ws.freeze_panes(HDR + 1, 0)
        if self.preview:
            ws.print_area(0, 0, HDR + 24, max(last, 8))
        ws.set_landscape(); ws.fit_to_pages(1, 0 if not self.preview else 1); ws.repeat_rows(HDR)
        return ws

    # ---------- KPI formulas ----------
    def kfmt(self, k):
        return {'money': '#,##0', 'pct': '0.0%', 'num': '#,##0.0'}.get(k.get('fmt'), '#,##0')

    def crit(self, m, w):
        out = []
        for fld, val in w:
            f = next(x for x in m['fields'] if x['k'] == fld)
            if isinstance(val, int) and f.get('t') == 'list':
                val = self.LIST[f['l']]['items'][val]
            if isinstance(val, str) and not val.startswith(('>', '<', '=')):
                out.append((self.rng(m, fld), lit(val)))
            else:
                out.append((self.rng(m, fld), lit(val) if isinstance(val, str) else str(val)))
        return out

    def kpi(self, m, k, S=None, E=None):
        conds = self.crit(m, k.get('w', []))
        dfield = (m.get('rep') or {}).get('date') if S else None
        if dfield:
            dr = self.rng(m, dfield)
            conds = [(dr, '">="&' + S), (dr, '"<"&' + E)] + conds
        a = k.get('a', 'count')
        keyr = self.rng(m, self.keyfield(m))
        pairs = lambda cs: ','.join('%s,%s' % (r_, c_) for r_, c_ in cs)
        if a == 'count':
            return 'COUNTIFS(%s)' % pairs(conds) if conds else 'COUNTIF(%s,"<>")' % keyr
        val = self.rng(m, k.get('f', ''))  if k.get('f') else None
        if a == 'sum':
            return 'SUMIFS(%s,%s)' % (val, pairs(conds)) if conds else 'SUM(%s)' % val
        if a == 'avg':
            return 'IFERROR(AVERAGEIFS(%s,%s),0)' % (val, pairs(conds)) if conds else 'IFERROR(AVERAGE(%s),0)' % val
        if a in ('max', 'min'):
            fn = a.upper()
            return 'IFERROR(%sIFS(%s,%s),0)' % (fn, val, pairs(conds)) if conds else '%s(%s)' % (fn, val)
        if a == 'pct':
            base = [c_ for c_ in conds if c_ not in self.crit(m, k.get('w', []))]
            den = 'COUNTIFS(%s)' % pairs(base) if base else 'COUNTIF(%s,"<>")' % keyr
            return 'IFERROR(COUNTIFS(%s)/%s,0)' % (pairs(conds), den)
        if a == 'ratio':
            v2 = self.rng(m, k['f2'])
            num = 'SUMIFS(%s,%s)' % (val, pairs(conds)) if conds else 'SUM(%s)' % val
            den = 'SUMIFS(%s,%s)' % (v2, pairs(conds)) if conds else 'SUM(%s)' % v2
            return 'IFERROR(%s/%s,0)' % (num, den)
        if a == 'sumpct':   # sum where w / total sum
            base = [c_ for c_ in conds if c_ not in self.crit(m, k.get('w', []))]
            den = 'SUMIFS(%s,%s)' % (val, pairs(base)) if base else 'SUM(%s)' % val
            return 'IFERROR(SUMIFS(%s,%s)/%s,0)' % (val, pairs(conds), den)
        raise ValueError('kpi agg ' + a)

    # ---------- reports ----------
    def build_report(self, m):
        rep = m.get('rep')
        if not rep:
            return
        t = self.t
        title = '%s - %s' % (self.L(m['n']), t['report']) if not self.t['rtl'] else '%s %s' % (t['report'], self.L(m['n']))
        ws, name = self.add(title, 'report', t['tip_rep'], tint(self.P, .35))
        self.banner(ws, title, t['tip_rep'], 16)
        self.navlinks(ws, 2, 1, 4)
        ws.set_column(1, 1, 22); ws.set_column(2, 5, 13); ws.set_column(6, 6, 3); ws.set_column(7, 19, 9)
        lab = self.F(bold=True, bg_color='#F4F6F8', border=1, border_color='#D0D5DA')
        inp = self.F(bold=True, bg_color='#FFF8DC', border=2, border_color=self.A, align='center', locked=False)
        ws.write(3, 1, t['year'], lab); ws.write_number(3, 2, TODAY.year, inp)
        ws.data_validation(3, 2, 3, 2, {'validate': 'list', 'source': self.YEARS})
        Y = '$C$4'
        h = self.F(bold=True, font_color='#FFFFFF', bg_color=self.P, align='center', border=1, border_color=self.P)
        cell = self.F(border=1, border_color='#E1E5EA', align='center', num_format='#,##0')
        cellm = self.F(border=1, border_color='#E1E5EA', align='center', num_format='#,##0')
        cellp = self.F(border=1, border_color='#E1E5EA', align='center', num_format='0.0%')
        lab2 = self.F(border=1, border_color='#E1E5EA', bold=True)
        val = rep.get('val'); agg = rep.get('agg', 'sum' if val else 'count')
        r = 5
        dfield = rep.get('date')
        vname = self.L(next(f['n'] for f in m['fields'] if f['k'] == val)) if val else ''
        aggname = {'sum': t['total'], 'avg': t['average'], 'count': t['count']}[agg]
        def aggf(conds):
            ps = ','.join('%s,%s' % x for x in conds)
            if agg == 'sum':
                return 'SUMIFS(%s,%s)' % (self.rng(m, val), ps)
            if agg == 'avg':
                return 'IFERROR(AVERAGEIFS(%s,%s),0)' % (self.rng(m, val), ps)
            return 'COUNTIFS(%s)' % ps
        if dfield:
            dr = self.rng(m, dfield)
            ws.merge_range(r, 1, r, 4, t['by_month'], self.F(bold=True, font_size=12, font_color=self.P))
            ws.write_row(r + 1, 1, [t['month'], t['count'], ('%s %s' % (aggname, vname)).strip() if val else '', t['share']], h)
            for i in range(12):
                rr = r + 2 + i
                ws.write(rr, 1, t['months'][i], lab2)
                S = 'DATE(%s,%d,1)' % (Y, i + 1); E = 'DATE(%s,%d,1)' % (Y, i + 2)
                conds = [(dr, '">="&' + S), (dr, '"<"&' + E)]
                self.fml(ws, rr, 2, 'COUNTIFS(%s)' % ','.join('%s,%s' % x for x in conds), cell)
                if val:
                    self.fml(ws, rr, 3, aggf(conds), cellm)
                    self.fml(ws, rr, 4, 'IFERROR(%s/SUM($D$%d:$D$%d),0)' % (rc(rr, 3), r + 3, r + 14) if agg == 'sum'
                             else 'IFERROR(%s/SUM($C$%d:$C$%d),0)' % (rc(rr, 2), r + 3, r + 14), cellp)
                else:
                    ws.write_blank(rr, 3, None, cell)
                    self.fml(ws, rr, 4, 'IFERROR(%s/SUM($C$%d:$C$%d),0)' % (rc(rr, 2), r + 3, r + 14), cellp)
            tr = r + 14
            ws.write(tr, 1, t['total'], h)
            self.fml(ws, tr, 2, 'SUM(C%d:C%d)' % (r + 3, r + 14), h)
            if val:
                self.fml(ws, tr, 3, ('SUM(D%d:D%d)' if agg == 'sum' else 'IFERROR(AVERAGE(D%d:D%d),0)') % (r + 3, r + 14), self.F(bold=True, font_color='#FFFFFF', bg_color=self.P, align='center', num_format='#,##0'))
            else:
                ws.write_blank(tr, 3, None, h)
            ws.write_blank(tr, 4, None, h)
            ws.conditional_format(r + 2, 3 if val else 2, r + 13, 3 if val else 2, {'type': 'data_bar', 'bar_color': tint(self.P, .4), 'bar_solid': True})
            # combo chart
            ch = self.wb.add_chart({'type': 'column'})
            vc = 3 if val else 2
            ch.add_series({'name': ('%s %s' % (aggname, vname)).strip() if val else t['count'], 'categories': [name, r + 2, 1, r + 13, 1],
                           'values': [name, r + 2, vc, r + 13, vc], 'fill': {'color': self.P}, 'gap': 60,
                           'data_labels': {'value': True, 'num_format': '#,##0', 'font': {'size': 8}}})
            if val:
                ln = self.wb.add_chart({'type': 'line'})
                ln.add_series({'name': t['count'], 'categories': [name, r + 2, 1, r + 13, 1], 'values': [name, r + 2, 2, r + 13, 2],
                               'y2_axis': True, 'line': {'color': self.A, 'width': 2.25}, 'marker': {'type': 'circle', 'size': 5,
                               'fill': {'color': self.A}, 'border': {'color': self.A}}})
                ch.combine(ln)
            self.style_chart(ch, t['by_month'] + ' - ' + self.L(m['n']), legend=bool(val))
            ch.set_size({'width': 700, 'height': 330})
            ws.insert_chart(r, 7, ch, {'x_offset': 5, 'y_offset': 5})
            r += 18
        for bi, bf in enumerate(rep.get('by', [])):
            f = next(x for x in m['fields'] if x['k'] == bf)
            items = self.LIST[f['l']]['items']
            col = self.LIST[f['l']]['col']
            ws.merge_range(r, 1, r, 4, '%s %s' % (t['by'], self.L(f['n'])), self.F(bold=True, font_size=12, font_color=self.P))
            ws.write_row(r + 1, 1, [self.L(f['n']), t['count'], ('%s %s' % (aggname, vname)).strip() if val else '', t['share']], h)
            n = len(items) + 5
            for i in range(n):
                rr = r + 2 + i
                src = "%s!$%s$%d" % (qs(self.set_name), col, 5 + i)
                self.fml(ws, rr, 1, 'IF(%s="","",%s)' % (src, src), lab2)
                conds = [(self.rng(m, bf), rc(rr, 1))]
                if dfield:
                    conds += [(self.rng(m, dfield), '">="&DATE(%s,1,1)' % Y), (self.rng(m, dfield), '"<"&DATE(%s+1,1,1)' % Y)]
                self.fml(ws, rr, 2, 'IF(%s="","",COUNTIFS(%s))' % (rc(rr, 1), ','.join('%s,%s' % x for x in conds)), cell)
                if val:
                    self.fml(ws, rr, 3, 'IF(%s="","",%s)' % (rc(rr, 1), aggf(conds)), cellm)
                else:
                    ws.write_blank(rr, 3, None, cell)
                base = 3 if (val and agg == 'sum') else 2
                L_ = cn(base)
                self.fml(ws, rr, 4, 'IF(%s="","",IFERROR(%s/SUM($%s$%d:$%s$%d),0))' % (rc(rr, 1), rc(rr, base), L_, r + 3, L_, r + 2 + n), cellp)
            ws.conditional_format(r + 2, 4, r + 1 + n, 4, {'type': 'data_bar', 'bar_color': tint(self.A, .3), 'bar_solid': True})
            vc = 3 if (val and agg == 'sum') else 2
            ctype = 'doughnut' if bi == 0 and len(items) <= 8 else 'bar'
            ch = self.wb.add_chart({'type': ctype})
            ser = {'name': self.L(f['n']), 'categories': [name, r + 2, 1, r + 1 + len(items), 1],
                   'values': [name, r + 2, vc, r + 1 + len(items), vc]}
            if ctype == 'doughnut':
                ser['points'] = [{'fill': {'color': self.PAL[i % len(self.PAL)]}} for i in range(len(items))]
                ser['data_labels'] = {'percentage': True, 'font': {'size': 8, 'color': '#FFFFFF', 'bold': True}}
            else:
                ser['fill'] = {'color': self.PAL[(bi + 1) % len(self.PAL)]}
                ser['gap'] = 50
                ser['data_labels'] = {'value': True, 'num_format': '#,##0', 'font': {'size': 8}}
            ch.add_series(ser)
            if ctype == 'doughnut':
                ch.set_hole_size(58)
            self.style_chart(ch, '%s %s' % (t['by'], self.L(f['n'])), legend=(ctype == 'doughnut'), bar=(ctype == 'bar'))
            ch.set_size({'width': 700, 'height': max(260, 26 * len(items) + 90)})
            ws.insert_chart(r, 7, ch, {'x_offset': 5, 'y_offset': 5})
            r += max(n + 4, 16 if ctype == 'doughnut' else int((26 * len(items) + 90) / 20) + 3)
        hm = rep.get('heat')
        if hm and dfield:
            f = next(x for x in m['fields'] if x['k'] == hm['rows'])
            items = self.LIST[f['l']]['items']
            hv, ha = hm.get('val'), hm.get('agg', 'avg')
            ws.merge_range(r, 1, r, 13, '%s: %s %s %s' % (t['heat'], self.L(f['n']), '×', t['month']), self.F(bold=True, font_size=12, font_color=self.P))
            ws.write(r + 1, 1, self.L(f['n']), h)
            for i in range(12):
                ws.write(r + 1, 2 + i, t['months'][i], h)
            ws.set_column(2, 13, 9.5)
            for j, it in enumerate(items):
                rr = r + 2 + j
                ws.write(rr, 1, it, lab2)
                for i in range(12):
                    conds = [(self.rng(m, hm['rows']), rc(rr, 1, False, True)),
                             (self.rng(m, dfield), '">="&DATE(%s,%d,1)' % (Y, i + 1)), (self.rng(m, dfield), '"<"&DATE(%s,%d,1)' % (Y, i + 2))]
                    ps = ','.join('%s,%s' % x for x in conds)
                    if ha == 'avg':
                        fx = 'IFERROR(AVERAGEIFS(%s,%s),"")' % (self.rng(m, hv), ps)
                    elif ha == 'sum':
                        fx = 'SUMIFS(%s,%s)' % (self.rng(m, hv), ps)
                    else:
                        fx = 'COUNTIFS(%s)' % ps
                    self.fml(ws, rr, 2 + i, fx, self.F(border=1, border_color='#FFFFFF', align='center', num_format='#,##0'))
            ws.conditional_format(r + 2, 2, r + 1 + len(items), 13, {'type': '3_color_scale', 'min_color': '#E8F6F3',
                                                                      'mid_color': '#F9E79F', 'max_color': '#E74C3C'})
            r += len(items) + 4
        if self.preview:
            ws.print_area(0, 0, r, 19)
        ws.set_landscape(); ws.fit_to_pages(1, 0 if not self.preview else 1)
        ws.protect('', {'autofilter': True, 'sort': True, 'format_columns': True, 'select_locked_cells': True})

    def style_chart(self, ch, title, legend=False, bar=False):
        self.stats['charts'] += 1
        ch.set_title({'name': title, 'name_font': {'size': 11, 'bold': True, 'color': '#2C3E50', 'name': self.font}})
        ch.set_chartarea({'border': {'none': True}, 'fill': {'color': '#FFFFFF'}})
        ch.set_plotarea({'fill': {'none': True}})
        if legend:
            ch.set_legend({'position': 'bottom', 'font': {'size': 8, 'name': self.font}})
        else:
            ch.set_legend({'none': True})
        try:
            cat = {'num_font': {'size': 8, 'name': self.font}, 'line': {'color': '#D5D8DC'}, 'major_gridlines': {'visible': False}}
            vax = {'num_font': {'size': 8, 'name': self.font}, 'line': {'none': True},
                   'major_gridlines': {'visible': True, 'line': {'color': '#EEF0F2'}}, 'num_format': '#,##0'}
            if bar:
                ch.set_x_axis(vax); ch.set_y_axis(cat)
            else:
                ch.set_x_axis(cat); ch.set_y_axis(vax)
        except Exception:
            pass

    # ---------- dashboard ----------
    def build_dashboard(self):
        t = self.t
        ds = self.s.get('dashboard', {})
        ws, name = self.add(t['dashboard'], 'dashboard', t['tip_dash'], self.A)
        assert name == self.dash_name
        cw = 9.2
        ws.set_column(0, 0, 1.5, self.F(bg_color=self.P)); ws.set_column(1, 1, 26, self.F(bg_color=self.P))
        ws.set_column(2, 2, 2); ws.set_column(3, 18, cw); ws.set_column(19, 19, 2)
        bg = self.F(bg_color='#F2F4F7')
        for r in range(0, 70):
            ws.set_row(r, 20, bg)
        sb = self.F(bg_color=self.P, font_color='#FFFFFF')
        sbt = self.F(bg_color=self.P, font_color='#FFFFFF', bold=True, font_size=14, text_wrap=True)
        sbl = self.F(bg_color=self.P, font_color=tint(self.P, .6), bold=True, font_size=9)
        sinp = self.F(bg_color='#FFFFFF', font_color=self.P, bold=True, align='center', border=1, border_color=self.A, locked=False)
        for r in range(0, 70):
            ws.write_blank(r, 0, None, sb); ws.write_blank(r, 1, None, sb)
        self.fml(ws, 1, 1, self.SET['biz_name'], sbt)
        ws.write(2, 1, self.L(self.s['title']), self.F(bg_color=self.P, font_color=tint(self.P, .7), font_size=9, text_wrap=True))
        ws.write(4, 1, t['filters'], sbl)
        ws.write(5, 1, t['year'], sb); ws.write_number(6, 1, TODAY.year, sinp)
        ws.data_validation(6, 1, 6, 1, {'validate': 'list', 'source': self.YEARS})
        ws.write(7, 1, t['month'], sb); ws.write(8, 1, t['all'], sinp)
        ws.data_validation(8, 1, 8, 1, {'validate': 'list', 'source': self.MONTHS_ALL})
        ws.write(10, 1, t['pages'], sbl)
        linkf = self.F(bg_color=self.P, font_color='#FFFFFF', underline=0, indent=1)
        rr = 11
        ws.write_url(rr, 1, "internal:%s!A1" % qs(self.start_name), linkf, string='▸ ' + t['start']); rr += 1
        for m in self.s.get('modules', []):
            if rr > 66:
                break
            ws.write_url(rr, 1, "internal:%s!A1" % qs(m['_sheet']), linkf, string='▸ ' + self.L(m['n'])); rr += 1
        # calc helpers on hidden sheet
        cs = self.calc_ws
        Yr, Mr = "%s!$B$7" % qs(name), "%s!$B$9" % qs(name)
        cs.write(0, 0, 'mi'); self.fml(cs, 0, 1, 'IFERROR(MATCH(%s,%s,0),0)' % (Mr, self.MONTHS))
        cs.write(1, 0, 'S'); self.fml(cs, 1, 1, 'IF(B1=0,DATE(%s,1,1),DATE(%s,B1,1))' % (Yr, Yr))
        cs.write(2, 0, 'E'); self.fml(cs, 2, 1, 'IF(B1=0,DATE(%s+1,1,1),DATE(%s,B1+1,1))' % (Yr, Yr))
        S, E = "%s!$B$2" % qs(self.calc_name), "%s!$B$3" % qs(self.calc_name)
        # header strip
        ws.merge_range(1, 3, 1, 18, self.L(self.s['title']), self.F(bold=True, font_size=16, font_color='#2C3E50', bg_color='#F2F4F7'))
        ws.set_row(1, 34, bg)
        self.fml(ws, 2, 3, '%s&"  |  "&IF(%s=%s,%s,%s&" "&%s)&"  |  "&%s' % (lit(t['period'] + ':'), Mr, lit(t['all']), Yr, Mr, Yr,
                                                                       self.SET['currency']),
                 self.F(font_color='#7F8C8D', bg_color='#F2F4F7'))
        # KPI cards: 4 per row
        kpis = ds.get('kpis', [])[:8]
        r0 = 4
        for i, (mid, ki) in enumerate(kpis):
            m = self.mods[mid]; k = m['kpis'][ki]
            row = r0 + (i // 4) * 4; col = 3 + (i % 4) * 4
            card = '#FFFFFF'
            ws.merge_range(row, col, row, col + 2, self.L(k['n']), self.F(font_size=9, font_color='#7F8C8D', bg_color=card, indent=1,
                                                                            top=1, left=1, right=1, border_color='#E3E7EB'))
            fmt = self.F(bold=True, font_size=20, font_color=self.PAL[i % len(self.PAL)] if i else self.P, bg_color=card, indent=1,
                         num_format=self.kfmt(k), left=1, right=1, border_color='#E3E7EB')
            ws.merge_range(row + 1, col, row + 1, col + 2, '', fmt)
            self.fml(ws, row + 1, col, self.kpi(m, k, S, E), fmt)
            ws.merge_range(row + 2, col, row + 2, col + 2, self.L(m['n']), self.F(font_size=8, font_color=tint(self.P, .3), bg_color=card, indent=1,
                                                                                bottom=1, left=1, right=1, border_color='#E3E7EB'))
            ws.set_row(row + 1, 34, bg)
        rows_used = r0 + ((len(kpis) + 3) // 4) * 4
        # charts 2 per row, helper data on hidden sheet
        hr = 5
        for i, chs in enumerate(ds.get('charts', [])[:8]):
            m = self.mods[chs['m']]
            rep = m.get('rep', {})
            val = chs.get('v', rep.get('val')); agg = chs.get('a', 'sum' if val else 'count')
            labels, fs = [], []
            dfield = rep.get('date')
            if chs['by'] == 'month':
                for mi_ in range(12):
                    labels.append(self.t['months'][mi_])
                    conds = [(self.rng(m, dfield), '">="&DATE(%s,%d,1)' % (Yr, mi_ + 1)), (self.rng(m, dfield), '"<"&DATE(%s,%d,1)' % (Yr, mi_ + 2))]
                    fs.append(conds)
            else:
                f = next(x for x in m['fields'] if x['k'] == chs['by'])
                for it in self.LIST[f['l']]['items']:
                    labels.append(it)
                    conds = [(self.rng(m, chs['by']), lit(it))]
                    if dfield:
                        conds += [(self.rng(m, dfield), '">="&' + S), (self.rng(m, dfield), '"<"&' + E)]
                    fs.append(conds)
            for j, (lb, conds) in enumerate(zip(labels, fs)):
                cs.write(hr + j, 0, lb)
                ps = ','.join('%s,%s' % x for x in conds)
                if agg == 'sum':
                    fx = 'SUMIFS(%s,%s)' % (self.rng(m, val), ps)
                elif agg == 'avg':
                    fx = 'IFERROR(AVERAGEIFS(%s,%s),0)' % (self.rng(m, val), ps)
                else:
                    fx = 'COUNTIFS(%s)' % ps
                self.fml(cs, hr + j, 1, fx)
            ctype = chs.get('t', 'column' if chs['by'] == 'month' else 'doughnut')
            ch = self.wb.add_chart({'type': 'area' if ctype == 'area' else ctype})
            ser = {'name': self.L(chs.get('n', m['n'])), 'categories': [self.calc_name, hr, 0, hr + len(labels) - 1, 0],
                   'values': [self.calc_name, hr, 1, hr + len(labels) - 1, 1]}
            colr = self.PAL[i % len(self.PAL)]
            if ctype in ('doughnut', 'pie'):
                ser['points'] = [{'fill': {'color': self.PAL[q % len(self.PAL)]}} for q in range(len(labels))]
                ser['data_labels'] = {'percentage': True, 'font': {'size': 8, 'color': '#FFFFFF', 'bold': True}}
            elif ctype == 'line':
                ser['line'] = {'color': colr, 'width': 2.5}; ser['marker'] = {'type': 'circle', 'size': 5, 'fill': {'color': colr}, 'border': {'color': colr}}
                ser['data_labels'] = {'value': True, 'num_format': '#,##0', 'font': {'size': 7}, 'position': 'above'}
            elif ctype == 'area':
                ser['fill'] = {'color': tint(colr, .45)}; ser['line'] = {'color': colr, 'width': 2}
            else:
                ser['fill'] = {'color': colr}; ser['gap'] = 55
                ser['data_labels'] = {'value': True, 'num_format': '#,##0', 'font': {'size': 7}}
            ch.add_series(ser)
            if ctype == 'doughnut':
                ch.set_hole_size(60)
            self.style_chart(ch, self.L(chs.get('n', m['n'])), legend=ctype in ('doughnut', 'pie'), bar=(ctype == 'bar'))
            ch.set_size({'width': 545, 'height': 290})
            row = rows_used + (i // 2) * 15; col = 3 + (i % 2) * 8
            ws.insert_chart(row, col, ch, {'x_offset': 2, 'y_offset': 4})
            hr += len(labels) + 2
        last = rows_used + ((len(ds.get('charts', [])[:8]) + 1) // 2) * 15
        if self.preview:
            ws.print_area(0, 0, last, 19)
        ws.set_landscape(); ws.fit_to_pages(1, 1)
        ws.protect('', {'select_locked_cells': True})
        ws.activate()

    # ---------- calculators ----------
    def build_calculator(self, c):
        t = self.t
        title = self.L(c['n'])
        ws, name = self.add(title, 'calculator', self.L(c.get('d', '')), '#2E86AB')
        wide = bool(c.get('table'))
        self.banner(ws, title, self.L(c.get('d', '')), 12 if wide else 2, '#1F618D')
        self.navlinks(ws, 2, 1, 2)
        ws.write(3, 1, t['tip_calc'], self.F(italic=True, font_color='#7F8C8D', font_size=8))
        ws.set_column(1, 1, 34); ws.set_column(2, 2, 18); ws.set_column(3, 3, 3); ws.set_column(4, 12, 14)
        h = self.F(bold=True, font_color='#FFFFFF', bg_color='#1F618D', border=1, border_color='#1F618D')
        lab = self.F(border=1, border_color='#E1E5EA', bg_color='#F8F9FA')
        nf = lambda k: {'money': '#,##0', 'pct': '0.00%', 'num': '#,##0.00', 'int': '#,##0', 'date': 'yyyy-mm-dd', 'years': '0.0'}.get(k, '#,##0.00')
        refs = {}
        ws.merge_range(4, 1, 4, 2, t['inputs'], h)
        r = 5
        for it in c['inputs']:
            ws.write(r, 1, self.L(it['n']), lab)
            fm = self.F(bg_color='#FFF8DC', border=1, border_color='#D4AC0D', bold=True, align='center', num_format=nf(it.get('fmt')), locked=False)
            v = it.get('v', 0)
            if it.get('fmt') == 'date':
                v = TODAY if v in (0, 'today') else dt.date.fromisoformat(v)
                ws.write_datetime(r, 2, dt.datetime.combine(v, dt.time()), fm)
            elif isinstance(v, str) and it.get('l'):
                ws.write_string(r, 2, v if v not in ('0',) else '', fm)
            elif isinstance(v, (int, float)):
                ws.write_number(r, 2, v, fm)
            else:
                ws.write_string(r, 2, self.L(v), fm)
            if it.get('l'):
                ws.data_validation(r, 2, r, 2, {'validate': 'list', 'source': '=' + self.LIST[it['l']]['name']})
            refs[it['k']] = '$C$%d' % (r + 1)
            r += 1
        r += 1
        ws.merge_range(r, 1, r, 2, t['results'], self.F(bold=True, font_color='#FFFFFF', bg_color=self.P, border=1, border_color=self.P))
        r += 1
        def res(expr, extra=None):
            d = dict(refs); d.update(extra or {})
            expr = self.common_tokens(expr)
            return re.sub(r'\{(\w+)\}', lambda mo: d[mo.group(1)], expr)
        for o in c['outputs']:
            hl = o.get('hl')
            ws.write(r, 1, self.L(o['n']), self.F(border=1, border_color='#E1E5EA', bold=bool(hl), bg_color='#EAF2F8' if hl else '#FFFFFF'))
            refs[o['k']] = '$C$%d' % (r + 1)
            self.fml(ws, r, 2, 'IFERROR(%s,0)' % res(o['f'].lstrip('=')), self.F(border=1, border_color='#E1E5EA', bold=True, align='center',
                     num_format=nf(o.get('fmt')), font_color=self.P if hl else '#2C3E50', font_size=12 if hl else 10,
                     bg_color='#EAF2F8' if hl else '#FFFFFF'))
            if hl:
                ws.set_row(r, 22)
            r += 1
        tb = c.get('table')
        if tb:
            tr0 = 4
            cols = [{'k': 'n', 'n': tb.get('nn', {'en': '#', 'ar': '#'}), 'fmt': 'int'}] + tb['cols']
            ws.merge_range(tr0, 4, tr0, 4 + len(cols) - 1, self.L(tb.get('title', t['schedule'])), self.F(bold=True, font_color='#FFFFFF', bg_color='#117A65', align='center'))
            for j, col in enumerate(cols):
                ws.write(tr0 + 1, 4 + j, self.L(col['n']), self.F(bold=True, bg_color='#D1F2EB', border=1, border_color='#A3E4D7', align='center', text_wrap=True))
            ws.set_row(tr0 + 1, 30)
            ccol = {col['k']: 4 + j for j, col in enumerate(cols)}
            nrows = tb.get('rows', 60)
            cond = tb.get('while')
            for i in range(nrows):
                rr = tr0 + 2 + i
                nref = rc(rr, 4)
                extra = {'n': nref}
                for col in cols:
                    if col['k'] == 'n':
                        continue
                    extra['c_' + col['k']] = rc(rr, ccol[col['k']])
                    extra['p_' + col['k']] = rc(rr - 1, ccol[col['k']]) if i > 0 else None
                prev_n = '0' if i == 0 else rc(rr - 1, 4)
                active = res(cond, {'n': '(%s+1)' % prev_n}) if cond else 'TRUE'
                self.fml(ws, rr, 4, 'IF(%s="","",IF(%s,%s+1,""))' % (prev_n, active, prev_n) if i else 'IF(%s,1,"")' % active,
                         self.F(align='center', border=1, border_color='#E1E5EA', font_color='#7F8C8D'))
                for col in cols[1:]:
                    src = col.get('f0') if (i == 0 and col.get('f0')) else col['f']
                    def tok(mo):
                        k = mo.group(1)
                        if k.startswith('p:'):
                            return extra['p_' + k[2:]] or '0'
                        if k.startswith('c:'):
                            return extra['c_' + k[2:]]
                        if k == 'n':
                            return nref
                        if k.startswith('S:'):
                            return self.SET[k[2:]]
                        return refs[k]
                    expr = re.sub(r'\{([\w:]+)\}', tok, self.common_tokens(src.lstrip('=')))
                    self.fml(ws, rr, ccol[col['k']], 'IF(%s="","",IFERROR(%s,0))' % (nref, expr),
                             self.F(border=1, border_color='#E1E5EA', align='center', num_format=nf(col.get('fmt'))))
            ch_spec = tb.get('chart')
            if ch_spec:
                ch = self.wb.add_chart({'type': ch_spec.get('t', 'line')})
                show = min(nrows, ch_spec.get('rows', nrows))
                for q, k in enumerate(ch_spec['cols']):
                    colr = self.PAL[q % len(self.PAL)]
                    s_ = {'name': self.L(next(x['n'] for x in cols if x['k'] == k)), 'categories': [name, tr0 + 2, 4, tr0 + 1 + show, 4],
                          'values': [name, tr0 + 2, ccol[k], tr0 + 1 + show, ccol[k]]}
                    if ch_spec.get('t', 'line') == 'line':
                        s_['line'] = {'color': colr, 'width': 2.25}
                    else:
                        s_['fill'] = {'color': colr}
                    ch.add_series(s_)
                self.style_chart(ch, self.L(tb.get('title', t['schedule'])), legend=len(ch_spec['cols']) > 1)
                ch.set_size({'width': 380, 'height': 260})
                ws.insert_chart(r + 1, 1, ch, {'x_offset': 2, 'y_offset': 8})
                r += 16
            if self.preview:
                ws.print_area(0, 0, max(r, tr0 + 30), 4 + len(cols))
        elif self.preview:
            ws.print_area(0, 0, r + 1, 12)
        ws.set_landscape(); ws.fit_to_pages(1, 0 if not self.preview else 1)
        ws.protect('', {'select_locked_cells': True, 'format_columns': True})

    # ---------- checklists ----------
    def build_checklist(self, c):
        t = self.t
        title = self.L(c['n'])
        ws, name = self.add(title, 'checklist', self.L(c.get('d', '')), '#76B041')
        self.banner(ws, title, self.L(c.get('d', '')), 7, '#4E7D2B')
        self.navlinks(ws, 2, 1, 3)
        ws.write(2, 5, t['tip_chk'], self.F(italic=True, font_color='#7F8C8D'))
        ws.set_column(1, 1, 5); ws.set_column(2, 2, 52); ws.set_column(3, 3, 18); ws.set_column(4, 4, 13); ws.set_column(5, 5, 10); ws.set_column(6, 6, 30)
        ws.write(4, 2, t['start_date'], self.F(bold=True, align='right' if not self.t['rtl'] else 'left'))
        ws.write_datetime(4, 3, dt.datetime.combine(TODAY, dt.time()), self.F(bg_color='#FFF8DC', border=2, border_color=self.A, num_format='yyyy-mm-dd', align='center', bold=True, locked=False))
        ws.write(5, 2, t['progress'], self.F(bold=True, align='right' if not self.t['rtl'] else 'left'))
        steps = c['steps']; r0 = 7; n = len(steps)
        self.fml(ws, 5, 3, 'IFERROR(COUNTIF(F%d:F%d,%s)/%d,0)' % (r0 + 2, r0 + 1 + n, lit(t['yes']), n),
                 self.F(bold=True, num_format='0%', align='center', font_color='#4E7D2B', font_size=14))
        ws.conditional_format(5, 3, 5, 3, {'type': 'data_bar', 'bar_color': '#A9DFBF', 'bar_solid': True, 'min_type': 'num', 'min_value': 0, 'max_type': 'num', 'max_value': 1})
        h = self.F(bold=True, font_color='#FFFFFF', bg_color='#4E7D2B', border=1, border_color='#4E7D2B', align='center')
        ws.write_row(r0, 1, ['#', t['step'], t['owner'], t['due'], t['done'], t['notes']], h)
        yn = '"%s,%s"' % (t['yes'], t['no'])
        for i, s in enumerate(steps):
            rr = r0 + 1 + i
            cellf = self.F(border=1, border_color='#E1E5EA', text_wrap=True)
            ws.write_number(rr, 1, i + 1, self.F(border=1, border_color='#E1E5EA', align='center', font_color='#7F8C8D'))
            ws.write(rr, 2, self.L(s['n']), cellf)
            ws.write(rr, 3, self.L(s.get('o', '')), self.F(border=1, border_color='#E1E5EA', locked=False))
            self.fml(ws, rr, 4, '$D$5+%d' % s.get('d', 0), self.F(border=1, border_color='#E1E5EA', num_format='yyyy-mm-dd', align='center'))
            ws.write(rr, 5, t['no'], self.F(border=1, border_color='#E1E5EA', align='center', locked=False, bold=True))
            ws.write_blank(rr, 6, None, self.F(border=1, border_color='#E1E5EA', locked=False))
        a, b = r0 + 1, r0 + n
        ws.data_validation(a, 5, b, 5, {'validate': 'list', 'source': [t['yes'], t['no']]})
        ws.conditional_format(a, 1, b, 6, {'type': 'formula', 'criteria': '=$F%d=%s' % (a + 1, lit(t['yes'])),
                                           'format': self.F(bg_color='#E9F7EF', font_color='#7DCEA0', font_strikeout=True)})
        ws.conditional_format(a, 1, b, 6, {'type': 'formula', 'criteria': '=AND($F%d<>%s,$E%d<TODAY())' % (a + 1, lit(t['yes']), a + 1),
                                           'format': self.F(bg_color='#FADBD8', font_color='#C0392B')})
        if self.preview:
            ws.print_area(0, 0, b + 1, 7)
        ws.fit_to_pages(1, 0 if not self.preview else 1)
        ws.protect('', {'select_locked_cells': True, 'format_columns': True, 'format_rows': True})

    # ---------- printable forms ----------
    def build_form(self, fm):
        t = self.t
        m = self.mods[fm['m']]
        title = self.L(fm['n'])
        ws, name = self.add(title, 'form', self.L(fm.get('d', '')), '#8E6C8A')
        ws.set_column(0, 0, 2); ws.set_column(1, 1, 26); ws.set_column(2, 4, 18); ws.set_column(5, 5, 2)
        self.navlinks(ws, 0, 1, 3)
        ws.write(1, 1, t['select_record'], self.F(bold=True))
        idr = self.rng(m, m['fields'][0]['k'])
        ws.write(1, 2, self.ids[m['id']][0], self.F(bg_color='#FFF8DC', border=2, border_color=self.A, bold=True, align='center', locked=False))
        ws.data_validation(1, 2, 1, 2, {'validate': 'list', 'source': '=' + idr})
        ws.write(1, 3, t['tip_form'], self.F(italic=True, font_color='#7F8C8D', font_size=8))
        sel = '$C$2'
        def look(k):
            if k.startswith('S:'):
                return self.SET[k[2:]]
            mo = re.match(r'@(\w+)\.(\w+)\|(\w+)$', k)
            if mo:
                om = self.mods[mo.group(1)]
                inner = 'INDEX(%s,MATCH(%s,%s,0))' % (self.rng(m, mo.group(3)), sel, idr)
                return 'IFERROR(INDEX(%s,MATCH(%s,%s,0)),"")' % (self.rng(om, mo.group(2)), inner, self.rng(om, om['fields'][0]['k']))
            f = next(x for x in m['fields'] if x['k'] == k)
            base = 'INDEX(%s,MATCH(%s,%s,0))' % (self.rng(m, k), sel, idr)
            tt = f.get('t') if f.get('t') != 'calc' else f.get('fmt')
            if tt == 'date':
                return 'IFERROR(TEXT(%s,"yyyy-mm-dd"),"")' % base
            if tt == 'money':
                return 'IFERROR(TEXT(%s,"#,##0")&" "&%s,"")' % (base, self.SET['currency'])
            if tt == 'pct':
                return 'IFERROR(TEXT(%s,"0.0%%"),"")' % base
            return 'IFERROR(%s&"","")' % base
        r = 3
        ws.set_row(r, 30)
        self.fml(ws, r, 1, self.SET['biz_name'], self.F(bold=True, font_size=16, font_color=self.P))
        self.fml(ws, r, 3, '%s&"  "&%s' % (self.SET['biz_phone'], self.SET['biz_email']), self.F(font_size=8, font_color='#7F8C8D', align='right' if not self.t['rtl'] else 'left'))
        r += 1
        ws.merge_range(r, 1, r, 4, '', self.F(bottom=2, bottom_color=self.P)); r += 2
        for b in fm['blocks']:
            bt = b['t']
            if bt == 'h':
                ws.set_row(r, 26)
                ws.merge_range(r, 1, r, 4, self.L(b['x']), self.F(bold=True, font_size=14, align='center', font_color='#2C3E50')); r += 2
            elif bt == 'kv':
                for k in b['f']:
                    lbl = self.L(next(x['n'] for x in m['fields'] if x['k'] == k)) if not k.startswith(('@', 'S:')) else self.L(b.get('labels', {}).get(k, k))
                    ws.write(r, 1, lbl, self.F(bold=True, bg_color='#F4F6F8', border=1, border_color='#D5D8DC'))
                    ws.merge_range(r, 2, r, 4, '', self.F(border=1, border_color='#D5D8DC'))
                    self.fml(ws, r, 2, look(k), self.F(border=1, border_color='#D5D8DC'))
                    r += 1
                r += 1
            elif bt == 'p':
                txt = self.L(b['x'])
                parts, last = [], 0
                for mo in re.finditer(r'\{([@\w.:|]+)\}', txt):
                    if mo.start() > last:
                        parts.append(lit(txt[last:mo.start()]))
                    parts.append(look(mo.group(1)))
                    last = mo.end()
                if last < len(txt):
                    parts.append(lit(txt[last:]))
                nlines = max(2, len(txt) // 85 + 1)
                ws.set_row(r, 15 * nlines)
                ws.merge_range(r, 1, r, 4, '', self.F(text_wrap=True, valign='top'))
                self.fml(ws, r, 1, '&'.join(parts) or '""', self.F(text_wrap=True, valign='top'))
                r += 2
            elif bt == 'sign':
                parties = [self.L(x) for x in b['x']]
                for j, p in enumerate(parties[:3]):
                    c = 1 + j * (4 // max(1, len(parties[:3]))) if len(parties) > 1 else 1
                    c = [1, 3, 4][j] if len(parties) == 2 and j == 1 else c
                    ws.write(r, c, p, self.F(bold=True))
                    ws.write(r + 2, c, t['signature'] + ': ............', self.F(font_color='#7F8C8D'))
                    ws.write(r + 3, c, t['date'] + ': ............', self.F(font_color='#7F8C8D'))
                r += 5
        ws.print_area(0, 1, r, 4)
        ws.fit_to_pages(1, 1); ws.set_paper(9); ws.center_horizontally(); ws.set_margins(0.5, 0.5, 0.6, 0.6)
        ws.protect('', {'select_locked_cells': True})

    # ---------- start + guide ----------
    def build_start(self):
        t = self.t
        ws, name = self.add(t['start'], 'start', self.L(self.s.get('subtitle', '')), self.P)
        assert name == self.start_name
        ws.set_column(0, 0, 2); ws.set_column(1, 16, 9.5)
        ws.set_row(0, 14)
        ws.merge_range(1, 1, 2, 16, self.L(self.s['title']), self.F(bold=True, font_size=22, font_color='#FFFFFF', bg_color=self.P, align='center'))
        ws.set_row(1, 30); ws.set_row(2, 30)
        ws.merge_range(3, 1, 3, 16, self.L(self.s.get('subtitle', '')), self.F(font_size=11, font_color='#FFFFFF', bg_color=tint(self.P, .2), align='center', text_wrap=True))
        ws.set_row(3, 34)
        ws.merge_range(4, 1, 4, 16, '', self.F(bg_color=self.A))
        ws.set_row(4, 4)
        r = 6
        groups = []
        def tile_list():
            out = [('dash', t['dashboard'], self.dash_name, t['dash_kind'])]
            return out
        sections = []
        sections.append((self.L(self.s.get('core_group', {'en': 'Overview', 'ar': 'نظرة عامة'})), self.A,
                         [(t['dashboard'], self.dash_name), (t['guide'], self.guide_name), (t['settings'], self.set_name)]))
        gdefs = self.s.get('groups', {})
        order = list(gdefs.keys())
        for gi, g in enumerate(order):
            items = []
            for m in self.s.get('modules', []):
                if m.get('g') == g:
                    items.append((self.L(m['n']), m['_sheet']))
                    if m.get('_rep_sheet'):
                        items.append(('%s ▪ %s' % (t['report'], self.L(m['n'])), m['_rep_sheet']))
            for kind, lst in (('calculators', 'calc'), ('checklists', 'chk'), ('forms', 'form')):
                for c in self.s.get(kind, []):
                    if c.get('g') == g and c.get('_sheet'):
                        items.append((self.L(c['n']), c['_sheet']))
            if items:
                sections.append((self.L(gdefs[g]), self.PAL[(gi + 1) % len(self.PAL)], items))
        loose = []
        for kind in ('calculators', 'checklists', 'forms'):
            for c in self.s.get(kind, []):
                if not c.get('g') and c.get('_sheet'):
                    loose.append((self.L(c['n']), c['_sheet']))
        if loose:
            sections.append((self.L({'en': 'Tools', 'ar': 'أدوات'}), '#1F618D', loose))
        for title, colr, items in sections:
            ws.merge_range(r, 1, r, 16, title, self.F(bold=True, font_size=13, font_color=colr, bottom=2, bottom_color=colr))
            ws.set_row(r, 24)
            r += 1
            for i, (lbl, sh) in enumerate(items):
                rr = r + (i // 4) * 3; cc = 1 + (i % 4) * 4
                f = self.F(bold=True, font_size=10, font_color='#FFFFFF' if i % 2 == 0 else colr, bg_color=colr if i % 2 == 0 else tint(colr, .82),
                           align='center', text_wrap=True, border=2, border_color='#FFFFFF')
                ws.merge_range(rr, cc, rr + 1, cc + 3, '', f)
                ws.write_url(rr, cc, "internal:%s!A1" % qs(sh), f, string=lbl)
            r += ((len(items) + 3) // 4) * 3 + 1
        ws.merge_range(r, 1, r, 16, t['sample_note'], self.F(italic=True, font_color='#7F8C8D', align='center'))
        if self.preview:
            ws.print_area(0, 0, r + 1, 17)
        ws.fit_to_pages(1, 1); ws.set_landscape()
        ws.protect('', {'select_locked_cells': True})

    def build_guide(self):
        t = self.t
        ws, name = self.add(t['guide'], 'guide', '', '#7F8C8D')
        assert name == self.guide_name
        ws.set_column(1, 1, 30); ws.set_column(2, 2, 16); ws.set_column(3, 3, 60); ws.set_column(4, 4, 50)
        self.banner(ws, t['guide'], self.L(self.s.get('subtitle', '')), 4)
        ws.write_url(2, 1, "internal:%s!A1" % qs(self.start_name), self.F(font_color=self.P, bold=True), string=t['menu'])
        h = self.F(bold=True, font_color='#FFFFFF', bg_color=self.P, border=1, border_color=self.P)
        ws.write_row(4, 1, [t['page'], '', t['what'], t['how']], h)
        tips = {'register': t['tip_reg'], 'report': t['tip_rep'], 'calculator': t['tip_calc'], 'checklist': t['tip_chk'],
                'form': t['tip_form'], 'dashboard': t['tip_dash'], 'settings': t['tip_set'], 'start': '', 'guide': ''}
        kinds = {'register': t['reg_kind'], 'report': t['rep_kind'], 'calculator': t['calc_kind'], 'checklist': t['chk_kind'],
                 'form': t['form_kind'], 'dashboard': t['dash_kind'], 'settings': t['settings'], 'start': t['start'], 'guide': t['guide']}
        r = 5
        for p in self.pages_plan:
            f = self.F(border=1, border_color='#E1E5EA', text_wrap=True, valign='top')
            ws.write_url(r, 1, "internal:%s!A1" % qs(p['sheet']), self.F(border=1, border_color='#E1E5EA', font_color=self.P, bold=True, valign='top'), string=p['title'])
            ws.write(r, 2, kinds.get(p['kind'], ''), f)
            ws.write(r, 3, p['desc'], f)
            ws.write(r, 4, tips.get(p['kind'], ''), f)
            r += 1
        if self.preview:
            ws.print_area(0, 0, min(r, 40), 5)
        ws.fit_to_pages(1, 0 if not self.preview else 1); ws.set_landscape()

    # ---------- main ----------
    def build(self):
        s = self.s
        self.prepare_modules()
        self.deferred()
        if self.preview:
            for w_ in self.wb.worksheets():
                w_.fit_to_pages(1, 1)
        self.wb.close()
        man = {'id': s['id'], 'lang': self.lang, 'title': self.L(s['title']), 'file': os.path.basename(self.path),
               'pages': self.pages, 'n_pages': len(self.pages), **self.stats,
               'modules': len(s.get('modules', [])), 'calculators': len(s.get('calculators', [])),
               'checklists': len(s.get('checklists', [])), 'forms': len(s.get('forms', []))}
        with open(self.path.replace('.xlsx', '.manifest.json'), 'w', encoding='utf-8') as fh:
            json.dump(man, fh, ensure_ascii=False, indent=1)
        return man

    def deferred(self):
        """xlsxwriter writes sheets in creation order and lets us fill any sheet at any time,
        so create every sheet first (display order), then fill them."""
        t, s = self.t, self.s
        plan = [('start', t['start']), ('dashboard', t['dashboard'])]
        for m in s.get('modules', []):
            plan.append(('register', m))
            if m.get('rep'):
                plan.append(('report', m))
        for c in s.get('calculators', []):
            plan.append(('calculator', c))
        for c in s.get('checklists', []):
            plan.append(('checklist', c))
        for f in s.get('forms', []):
            plan.append(('form', f))
        plan += [('guide', t['guide']), ('settings', t['settings'])]
        real_add = self.add
        created = {}
        def make(kind, obj):
            if kind in ('start', 'dashboard', 'guide', 'settings'):
                title = obj; desc = {'start': self.L(s.get('subtitle', '')), 'dashboard': t['tip_dash'], 'guide': '', 'settings': t['tip_set']}[kind]
            elif kind == 'report':
                title = ('%s - %s' % (self.L(obj['n']), t['report'])) if not t['rtl'] else '%s %s' % (t['report'], self.L(obj['n']))
                desc = t['tip_rep']
            else:
                title = self.L(obj['n']); desc = self.L(obj.get('d', ''))
            ws, name = real_add(title, kind, desc)
            return ws, name
        sheets = []
        for kind, obj in plan:
            ws, name = make(kind, obj)
            sheets.append((kind, obj, ws, name))
            if kind == 'start': self.start_name = name
            if kind == 'dashboard': self.dash_name = name
            if kind == 'guide': self.guide_name = name
            if kind == 'settings': self.set_name = name
            if kind == 'register': obj['_sheet'] = name
            if kind == 'report': obj['_rep_sheet'] = name
            if kind in ('calculator', 'checklist', 'form'): obj['_sheet'] = name
        self.calc_ws = self.wb.add_worksheet('_calc'); self.calc_name = '_calc'; self.calc_ws.hide()
        self.pages_plan = list(self.pages)
        # now fill: route add() to the pre-created sheet
        queue = {(kind, id(obj) if not isinstance(obj, str) else 0): (ws, name) for kind, obj, ws, name in sheets}
        def fake_add(title, kind, desc='', tab=None):
            key = (kind, 0 if kind in ('start', 'dashboard', 'guide', 'settings') else id(self._cur))
            ws, name = queue[key]
            if tab:
                ws.set_tab_color(tab)
            return ws, name
        self.add = fake_add
        self.pages_final = self.pages
        self._cur = None
        self.build_settings()
        for kind, obj, ws, name in sheets:
            self._cur = obj
            if kind == 'register': self.build_register(obj)
        for kind, obj, ws, name in sheets:
            self._cur = obj
            if kind == 'report': self.build_report(obj)
            elif kind == 'calculator': self.build_calculator(obj)
            elif kind == 'checklist': self.build_checklist(obj)
            elif kind == 'form': self.build_form(obj)
        self.build_dashboard()
        self.build_start()
        self.build_guide()
        self.add = real_add


def main():
    a = sys.argv[1:]
    spec = json.load(open(a[0], encoding='utf-8'))
    lang = a[a.index('--lang') + 1] if '--lang' in a else 'en'
    out = a[a.index('--out') + 1] if '--out' in a else '.'
    man = Book(spec, lang, out, preview='--preview' in a).build()
    print(json.dumps({k: v for k, v in man.items() if k != 'pages'}, ensure_ascii=False))


if __name__ == '__main__':
    main()
