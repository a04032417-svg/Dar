#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Quality gate: open the workbook in a real spreadsheet engine (LibreOffice), recalculate
every formula, and fail on any error value, dead page, broken link or missing chart.

    python3 validate.py BOOK.xlsx [--json]        exit code 0 = passed
"""
import json, os, re, subprocess, sys, tempfile, zipfile, shutil
import openpyxl

ERR = re.compile(r'^(#REF!|#NAME\?|#VALUE!|#DIV/0!|#N/A|#NUM!|#NULL!|Err:\d+|#ERR)')
PROFILE = os.path.join(tempfile.gettempdir(), 'dar_lo_profile')
XCU = """<?xml version="1.0" encoding="UTF-8"?>
<oor:items xmlns:oor="http://openoffice.org/2001/registry" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
<item oor:path="/org.openoffice.Office.Calc/Formula/Load"><prop oor:name="OOXMLRecalcMode" oor:op="fuse"><value>0</value></prop></item>
<item oor:path="/org.openoffice.Office.Calc/Formula/Load"><prop oor:name="ODFRecalcMode" oor:op="fuse"><value>0</value></prop></item>
</oor:items>"""


def lo(args, timeout=600):
    u = os.path.join(PROFILE, 'user')
    os.makedirs(u, exist_ok=True)
    with open(os.path.join(u, 'registrymodifications.xcu'), 'w') as fh:
        fh.write(XCU)
    cmd = ['soffice', '-env:UserInstallation=file://' + PROFILE, '--headless', '--norestore'] + args
    return subprocess.run(cmd, capture_output=True, text=True, timeout=timeout)


def recalc(path, outdir, fmt='xlsx'):
    os.makedirs(outdir, exist_ok=True)
    flt = {'xlsx': 'xlsx:Calc Office Open XML', 'pdf': 'pdf:calc_pdf_Export'}[fmt]
    r = lo(['--convert-to', flt, '--outdir', outdir, path])
    out = os.path.join(outdir, os.path.splitext(os.path.basename(path))[0] + '.' + fmt)
    if not os.path.exists(out):
        raise RuntimeError('LibreOffice failed: ' + r.stderr[-500:])
    return out


def check(path):
    rep = {'file': os.path.basename(path), 'errors': [], 'dead': [], 'links': [], 'ok': False}
    man_p = path.replace('.xlsx', '.manifest.json')
    man = json.load(open(man_p, encoding='utf-8')) if os.path.exists(man_p) else {'pages': []}
    kinds = {p['sheet']: p['kind'] for p in man.get('pages', [])}
    with zipfile.ZipFile(path) as z:
        rep['charts'] = len([n for n in z.namelist() if re.match(r'xl/charts/chart\d+\.xml$', n)])
    wf = openpyxl.load_workbook(path)                          # formulas + links
    names = set(wf.sheetnames)
    nform = 0
    for ws in wf.worksheets:
        for row in ws.iter_rows():
            for c in row:
                if isinstance(c.value, str) and c.value.startswith('='):
                    nform += 1
                if c.hyperlink is not None and c.hyperlink.location:
                    tgt = c.hyperlink.location.split('!')[0].strip("'").replace("''", "'")
                    if tgt not in names:
                        rep['links'].append('%s!%s -> %s' % (ws.title, c.coordinate, tgt))
    rep['formulas'] = nform
    tmp = tempfile.mkdtemp()
    try:
        rc_path = recalc(path, tmp)
        wv = openpyxl.load_workbook(rc_path, data_only=True)
        for ws in wv.worksheets:
            nonzero = 0; numeric = 0
            for row in ws.iter_rows():
                for c in row:
                    v = c.value
                    if isinstance(v, str) and ERR.match(v):
                        if len(rep['errors']) < 40:
                            rep['errors'].append('%s!%s %s' % (ws.title, c.coordinate, v))
                        rep.setdefault('n_errors', 0); rep['n_errors'] = rep.get('n_errors', 0) + 1
                    elif isinstance(v, (int, float)) and not isinstance(v, bool):
                        numeric += 1
                        if v != 0:
                            nonzero += 1
            k = kinds.get(ws.title, 'calc' if ws.title == '_calc' else '')
            if k in ('report', 'dashboard', 'calculator', 'calc') and nonzero == 0:
                rep['dead'].append(ws.title)
            if ws.title == '_calc':
                vals = [r[1].value for r in ws.iter_rows(min_row=6) if r[0].value not in (None, '')]
                zero = sum(1 for v in vals if not v)
                rep['calc_series'] = {'cells': len(vals), 'zero': zero}
    finally:
        shutil.rmtree(tmp, ignore_errors=True)
    rep['pages'] = len(man.get('pages', [])) or len(wf.sheetnames)
    rep['ok'] = not rep['errors'] and not rep['dead'] and not rep['links'] and rep['charts'] > 0
    return rep


if __name__ == '__main__':
    r = check(sys.argv[1])
    print(json.dumps(r, ensure_ascii=False, indent=None if '--json' in sys.argv else 1))
    sys.exit(0 if r['ok'] else 1)
