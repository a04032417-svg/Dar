#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Store images for one workbook in one language: a cover, one explained image per page type,
and contact sheets that show EVERY page with its name.

    python3 preview.py SPEC.json --lang en --out OUTDIR
Writes OUTDIR/<id>-<lang>/NN-*.png and images.json (file, alt text)."""
import base64, glob, html, json, os, shutil, subprocess, sys, tempfile
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from build import Book
from i18n import T
import validate

W, H = 1600, 1200
TXT = {
 'en': {'pages': 'pages', 'charts': 'charts', 'calcs': 'calculators', 'forms': 'printable forms', 'chk': 'checklists', 'formulas': 'live formulas',
        'all': 'Every page in this workbook', 'of': 'of', 'works': 'Works in Microsoft Excel 2019 / 365 and LibreOffice',
        'langs': 'Arabic & English editions included', 'tested': 'Every formula recalculated and tested - zero errors',
        'kinds': {'start': 'Start menu', 'dashboard': 'Dashboard', 'register': 'Register', 'report': 'Report', 'calculator': 'Calculator',
                  'checklist': 'Checklist', 'form': 'Printable form', 'guide': 'Guide', 'settings': 'Settings'},
        'why': {'start': ['One click to every page', 'Grouped by how you actually work', 'Your business name everywhere'],
                'dashboard': ['Pick year and month - everything follows', 'KPI cards and live charts', 'Built from your own records'],
                'register': ['Drop-down lists, dates and checks', 'Grey columns calculate themselves', 'Colour alerts for what needs action'],
                'report': ['Month by month with combo chart', 'Breakdowns with shares and charts', 'Heat maps that show hot spots'],
                'calculator': ['Change the yellow cells only', 'Answers update instantly', 'Full schedule and chart where useful'],
                'checklist': ['Ready steps with owners', 'Due dates from your start date', 'Late steps turn red automatically'],
                'form': ['Pick a record - the form fills itself', 'Your logo text, phone and email', 'Prints on one clean page']}},
 'ar': {'pages': 'صفحة', 'charts': 'رسماً بيانياً', 'calcs': 'حاسبة', 'forms': 'نموذجاً للطباعة', 'chk': 'قائمة تحقق', 'formulas': 'معادلة حية',
        'all': 'كل صفحات هذا الملف', 'of': 'من', 'works': 'يعمل على مايكروسوفت إكسل 2019 / 365 وليبر أوفيس',
        'langs': 'يشمل النسختين العربية والإنجليزية', 'tested': 'أُعيد حساب كل معادلة واختُبرت - بلا أي خطأ',
        'kinds': {'start': 'قائمة البداية', 'dashboard': 'لوحة التحكم', 'register': 'سجل', 'report': 'تقرير', 'calculator': 'حاسبة',
                  'checklist': 'قائمة تحقق', 'form': 'نموذج للطباعة', 'guide': 'الدليل', 'settings': 'الإعدادات'},
        'why': {'start': ['نقرة واحدة لكل صفحة', 'مرتبة حسب طريقة عملك الفعلية', 'اسم منشأتك في كل مكان'],
                'dashboard': ['اختر السنة والشهر فيتبعها كل شيء', 'بطاقات مؤشرات ورسوم حية', 'مبنية من سجلاتك أنت'],
                'register': ['قوائم منسدلة وتواريخ وتحقق', 'الأعمدة الرمادية تُحسب تلقائياً', 'تنبيهات ملونة لما يحتاج إجراء'],
                'report': ['شهراً بشهر مع رسم مركب', 'تفصيلات بالنسب والرسوم', 'خرائط حرارية تكشف المناطق الساخنة'],
                'calculator': ['غيّر الخلايا الصفراء فقط', 'النتائج تتحدث فوراً', 'جدول ورسم كامل عند الحاجة'],
                'checklist': ['خطوات جاهزة بالمسؤولين', 'مواعيد من تاريخ البدء', 'المتأخر يتحول للأحمر تلقائياً'],
                'form': ['اختر السجل فيمتلئ النموذج', 'اسم منشأتك وهاتفك وبريدك', 'يُطبع في صفحة واحدة أنيقة']}},
}


def b64(p):
    return 'data:image/png;base64,' + base64.b64encode(open(p, 'rb').read()).decode()


def shoot(pages_html, out_dir):
    from playwright.sync_api import sync_playwright
    with sync_playwright() as pw:
        br = pw.chromium.launch()
        pg = br.new_page(viewport={'width': W, 'height': H}, device_scale_factor=1)
        for name, doc in pages_html:
            pg.set_content(doc, wait_until='load')
            pg.screenshot(path=os.path.join(out_dir, name))
        br.close()


def frame(css_extra, body, rtl, P, A):
    return """<!doctype html><html dir="%s"><head><meta charset="utf-8"><style>
*{box-sizing:border-box;margin:0;padding:0} body{width:%dpx;height:%dpx;font-family:'Segoe UI','DejaVu Sans',Arial,sans-serif;
background:linear-gradient(135deg,%s 0%%,%s 100%%);color:#fff;overflow:hidden;position:relative}
.card{background:#fff;border-radius:18px;box-shadow:0 24px 60px rgba(0,0,0,.35);overflow:hidden}
.card img{display:block;width:100%%} .chip{display:inline-block;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.3);
border-radius:40px;padding:10px 22px;margin:6px;font-size:22px} .chip b{color:%s;font-size:30px;margin:0 6px}
.brand{position:absolute;top:18px;%s:40px;font-size:15px;opacity:.6;letter-spacing:2px}
%s</style></head><body>%s<div class="brand">DAR TEMPLATES</div></body></html>""" % (
        'rtl' if rtl else 'ltr', W, H, P, shade(P), A, 'left' if rtl else 'right', css_extra, body)


def shade(h, f=0.45):
    h = h.lstrip('#'); r, g, b = int(h[0:2], 16), int(h[2:4], 16), int(h[4:6], 16)
    return '#%02X%02X%02X' % tuple(int(x * f) for x in (r, g, b))


def run(spec_path, lang, out):
    spec = json.load(open(spec_path, encoding='utf-8'))
    tmp = tempfile.mkdtemp()
    man = Book(json.load(open(spec_path, encoding='utf-8')), lang, tmp, preview=True).build()
    pdf = validate.recalc(os.path.join(tmp, man['file']), tmp, 'pdf')
    subprocess.run(['pdftoppm', '-r', '70', '-png', pdf, os.path.join(tmp, 'p')], check=True)
    shots = sorted(glob.glob(os.path.join(tmp, 'p-*.png')))
    from PIL import Image, ImageChops
    for sp in shots:                       # trim the white paper margins around each page
        im = Image.open(sp).convert('RGB')
        bb = ImageChops.difference(im, Image.new('RGB', im.size, (255, 255, 255))).point(lambda v: 255 if v > 12 else 0).getbbox()
        if bb:
            pad = 8
            im.crop((max(0, bb[0] - pad), max(0, bb[1] - pad), min(im.width, bb[2] + pad), min(im.height, bb[3] + pad))).save(sp)
    pages = man['pages']
    if len(shots) != len(pages):
        print('warning: %d images for %d pages' % (len(shots), len(pages)), file=sys.stderr)
    pages = [dict(p, img=s) for p, s in zip(pages, shots)]
    tx = TXT.get(lang, TXT['en']); rtl = T.get(lang, T['en'])['rtl']
    P = spec.get('theme', {}).get('primary', '#0F5E63'); A = spec.get('theme', {}).get('accent', '#F2A541')
    L = lambda x: (x.get(lang) or x.get('en')) if isinstance(x, dict) else (x or '')
    od = os.path.join(out, '%s-%s' % (spec['id'], lang)); os.makedirs(od, exist_ok=True)
    jobs, alts = [], []
    dash = next((p for p in pages if p['kind'] == 'dashboard'), pages[0])
    stats = [(man['n_pages'], tx['pages']), (man['charts'], tx['charts']), (man['calculators'], tx['calcs']),
             (man['forms'], tx['forms']), (man['checklists'], tx['chk']), ('{:,}'.format(man['formulas']), tx['formulas'])]
    chips = ''.join('<span class="chip"><b>%s</b>%s</span>' % (a, html.escape(str(b))) for a, b in stats)
    body = """<div style="padding:56px 60px 0"><div style="font-size:24px;color:%s;letter-spacing:2px">%s</div>
<h1 style="font-size:64px;line-height:1.1;margin:10px 0 14px">%s</h1><p style="font-size:24px;opacity:.9;max-width:1400px">%s</p>
<div style="margin:22px 0 0 -6px">%s</div></div>
<div class="card" style="position:absolute;top:345px;left:90px;right:90px;bottom:84px;background:#F2F4F7"><img src="%s" style="object-fit:contain;object-position:top;height:100%%;width:100%%"></div>
<div style="position:absolute;bottom:36px;width:100%%;text-align:center;font-size:20px;opacity:.9">✓ %s &nbsp;&nbsp; ✓ %s &nbsp;&nbsp; ✓ %s</div>""" % (
        A, html.escape(L(spec.get('suite', {'en': 'DAR TEMPLATES PRO', 'ar': 'قوالب دار الاحترافية'}))), html.escape(man['title']),
        html.escape(L(spec.get('subtitle', ''))), chips, b64(dash['img']), tx['tested'], tx['langs'], tx['works'])
    jobs.append(('01-cover.png', frame('', body, rtl, P, A))); alts.append(('01-cover.png', man['title']))
    # one explained image per page kind
    n = 2
    for kind in ('dashboard', 'start', 'register', 'report', 'calculator', 'form', 'checklist'):
        ps = [p for p in pages if p['kind'] == kind]
        if not ps:
            continue
        p = ps[0] if kind in ('dashboard', 'start') else max(ps[:8], key=lambda q: os.path.getsize(q['img']))
        others = ' · '.join(html.escape(q['title']) for q in ps[1:9])
        bullets = ''.join('<li>%s</li>' % html.escape(b) for b in tx['why'][kind])
        body = """<div style="display:flex;height:100%%;padding:54px 50px 60px;gap:40px;align-items:center">
<div style="flex:0 0 430px"><div style="font-size:22px;color:%s;letter-spacing:2px">%s%s</div><h2 style="font-size:44px;margin:10px 0 18px;line-height:1.15">%s</h2>
<p style="font-size:21px;opacity:.9;margin-bottom:22px">%s</p><ul style="font-size:22px;line-height:1.7;padding-%s:26px">%s</ul>
<p style="font-size:16px;opacity:.75;margin-top:26px;line-height:1.5">%s</p></div>
<div class="card" style="flex:1;max-height:1060px"><img src="%s"></div></div>""" % (
            A, tx['kinds'][kind].upper() if lang == 'en' else tx['kinds'][kind], ' (%d)' % len(ps) if len(ps) > 1 else '', html.escape(p['title']),
            html.escape(p['desc'] or ''), 'right' if rtl else 'left', bullets, ('+ ' + others) if others else '', b64(p['img']))
        name = '%02d-%s.png' % (n, kind); n += 1
        jobs.append((name, frame('', body, rtl, P, A))); alts.append((name, '%s - %s' % (tx['kinds'][kind], p['title'])))
    # contact sheets: every page
    per = 12
    for s in range(0, len(pages), per):
        grp = pages[s:s + per]
        cells = ''.join("""<div style="background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,.3)">
<div style="height:248px;overflow:hidden;background:#fff"><img src="%s" style="width:100%%;display:block"></div>
<div style="background:%s;color:#fff;padding:8px 12px;font-size:16px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
<span style="color:%s;font-weight:bold">%d</span> &nbsp;%s <span style="opacity:.7;font-size:13px">· %s</span></div></div>""" % (
            b64(p['img']), P, A, s + i + 1, html.escape(p['title']), tx['kinds'].get(p['kind'], '')) for i, p in enumerate(grp))
        body = """<div style="padding:40px 46px"><div style="display:flex;justify-content:space-between;align-items:baseline">
<h2 style="font-size:36px">%s</h2><div style="font-size:22px;opacity:.85">%d-%d %s %d</div></div>
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:22px;margin-top:26px">%s</div></div>""" % (
            tx['all'], s + 1, s + len(grp), tx['of'], len(pages), cells)
        name = '%02d-all-pages-%d.png' % (n, s // per + 1); n += 1
        jobs.append((name, frame('', body, rtl, P, A))); alts.append((name, '%s %d-%d' % (tx['all'], s + 1, s + len(grp))))
    shoot(jobs, od)
    json.dump([{'file': a, 'alt': b} for a, b in alts], open(os.path.join(od, 'images.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
    shutil.rmtree(tmp, ignore_errors=True)
    return od, len(jobs)


if __name__ == '__main__':
    a = sys.argv[1:]
    od, k = run(a[0], a[a.index('--lang') + 1] if '--lang' in a else 'en', a[a.index('--out') + 1] if '--out' in a else '.')
    print(od, k)
