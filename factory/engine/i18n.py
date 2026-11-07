# -*- coding: utf-8 -*-
"""Fixed interface strings of every Dar Templates workbook, per language.
Profession content (names of modules, fields, lists...) comes from the spec; only the
engine's own words live here. Add a language by adding one dict with the same keys."""

T = {
 'en': {
  'rtl': False,
  'months': ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
  'all': 'All',
  'start': 'Start', 'dashboard': 'Dashboard', 'guide': 'Guide', 'settings': 'Settings',
  'report': 'Report', 'menu': '◂ Menu', 'back_dash': 'Dashboard ▸',
  'year': 'Year', 'month': 'Month', 'period': 'Period', 'filters': 'Filters', 'pages': 'Pages',
  'count': 'Count', 'total': 'Total', 'average': 'Average', 'share': 'Share', 'item': 'Item',
  'by_month': 'Month by month', 'by': 'By', 'monthly': 'Monthly', 'heat': 'Heat map',
  'records': 'Records', 'inputs': 'Inputs', 'results': 'Results', 'schedule': 'Schedule',
  'step': 'Step', 'owner': 'Owner', 'due': 'Due', 'done': 'Done', 'notes': 'Notes', 'progress': 'Progress',
  'start_date': 'Start date', 'yes': 'Yes', 'no': 'No', 'overdue': 'Overdue',
  'select_record': 'Select record', 'signature': 'Signature', 'date': 'Date', 'name': 'Name',
  'page': 'Page', 'what': 'What it does', 'how': 'How to use it',
  'biz': 'Business details', 'biz_name': 'Business name', 'biz_phone': 'Phone', 'biz_email': 'Email',
  'biz_addr': 'Address', 'currency': 'Currency', 'lists': 'Drop-down lists (edit freely)',
  'years': 'Years', 'my_business': 'My Business',
  'tip_reg': 'Type a new record on the first empty row. Drop-downs, dates and totals are ready; grey columns calculate themselves.',
  'tip_rep': 'Choose the year at the top; every table and chart updates instantly.',
  'tip_calc': 'Change the light-yellow cells; the results and the schedule recalculate at once.',
  'tip_chk': 'Set the start date, then mark each step as done. Late steps turn red.',
  'tip_form': 'Choose the record at the top, then print (fits one page).',
  'tip_dash': 'Choose the year and month on the side panel; every card and chart follows.',
  'tip_set': 'Your business name, currency and every drop-down list. Edit them and the whole workbook follows.',
  'sample_note': 'Sample data is included so you can see everything working - overwrite or delete it.',
  'calc_kind': 'Calculator', 'chk_kind': 'Checklist', 'form_kind': 'Printable form', 'reg_kind': 'Register',
  'rep_kind': 'Report', 'dash_kind': 'Dashboard',
 },
 'ar': {
  'rtl': True,
  'months': ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
  'all': 'الكل',
  'start': 'البداية', 'dashboard': 'لوحة التحكم', 'guide': 'الدليل', 'settings': 'الإعدادات',
  'report': 'تقرير', 'menu': 'القائمة ▸', 'back_dash': '◂ لوحة التحكم',
  'year': 'السنة', 'month': 'الشهر', 'period': 'الفترة', 'filters': 'الفلاتر', 'pages': 'الصفحات',
  'count': 'العدد', 'total': 'الإجمالي', 'average': 'المتوسط', 'share': 'النسبة', 'item': 'البند',
  'by_month': 'شهراً بشهر', 'by': 'حسب', 'monthly': 'شهري', 'heat': 'الخريطة الحرارية',
  'records': 'السجلات', 'inputs': 'المدخلات', 'results': 'النتائج', 'schedule': 'الجدول',
  'step': 'الخطوة', 'owner': 'المسؤول', 'due': 'الاستحقاق', 'done': 'تم', 'notes': 'ملاحظات', 'progress': 'الإنجاز',
  'start_date': 'تاريخ البدء', 'yes': 'نعم', 'no': 'لا', 'overdue': 'متأخر',
  'select_record': 'اختر السجل', 'signature': 'التوقيع', 'date': 'التاريخ', 'name': 'الاسم',
  'page': 'الصفحة', 'what': 'ماذا تفعل', 'how': 'طريقة الاستخدام',
  'biz': 'بيانات المنشأة', 'biz_name': 'اسم المنشأة', 'biz_phone': 'الهاتف', 'biz_email': 'البريد',
  'biz_addr': 'العنوان', 'currency': 'العملة', 'lists': 'القوائم المنسدلة (عدّلها بحرية)',
  'years': 'السنوات', 'my_business': 'منشأتي',
  'tip_reg': 'اكتب السجل الجديد في أول صف فارغ. القوائم والتواريخ جاهزة، والأعمدة الرمادية تُحسب تلقائياً.',
  'tip_rep': 'اختر السنة في الأعلى، فتتحدث كل الجداول والرسوم فوراً.',
  'tip_calc': 'غيّر الخلايا الصفراء الفاتحة، فتُعاد النتائج والجدول فوراً.',
  'tip_chk': 'حدد تاريخ البدء ثم علّم كل خطوة عند إنجازها. الخطوات المتأخرة تظهر بالأحمر.',
  'tip_form': 'اختر السجل في الأعلى ثم اطبع (صفحة واحدة).',
  'tip_dash': 'اختر السنة والشهر من اللوحة الجانبية، فتتبعها كل البطاقات والرسوم.',
  'tip_set': 'اسم منشأتك والعملة وكل القوائم المنسدلة. عدّلها فيتبعها الملف كله.',
  'sample_note': 'أُرفقت بيانات تجريبية لترى كل شيء يعمل؛ استبدلها أو احذفها.',
  'calc_kind': 'حاسبة', 'chk_kind': 'قائمة تحقق', 'form_kind': 'نموذج للطباعة', 'reg_kind': 'سجل',
  'rep_kind': 'تقرير', 'dash_kind': 'لوحة تحكم',
 },
}

# sample people / companies per language (fictional)
NAMES = {
 'en': ['James Carter', 'Olivia Brooks', 'Liam Turner', 'Emma Hayes', 'Noah Bennett', 'Ava Collins', 'Lucas Reed',
        'Mia Foster', 'Ethan Price', 'Sophia Ward', 'Mason Gray', 'Isabella Cole', 'Logan Hunt', 'Amelia Stone',
        'Henry Walsh', 'Grace Lane', 'Daniel Ross', 'Chloe Banks', 'Owen Fields', 'Lily Morgan'],
 'ar': ['محمد العتيبي', 'سارة القحطاني', 'عبدالله الشهري', 'نورة الدوسري', 'خالد الزهراني', 'ريم المطيري', 'فهد الغامدي',
        'هند الحربي', 'سلطان العنزي', 'لمى السبيعي', 'يوسف المالكي', 'دانة الشمري', 'عمر الرشيدي', 'جود البقمي',
        'ماجد العمري', 'رهف السلمي', 'تركي الجهني', 'أمل الخالدي', 'بندر الثبيتي', 'منى الأحمدي'],
}
COMPANIES = {
 'en': ['Northgate Holdings', 'Bluestone Partners', 'Crescent Group', 'Harbor Point LLC', 'Summit Ventures',
        'Oakridge Capital', 'Silverline Co.', 'Meridian Estates', 'Keystone Trading', 'Brightwater Inc.'],
 'ar': ['شركة الأفق القابضة', 'مجموعة الصخرة الزرقاء', 'شركة الهلال', 'مؤسسة الميناء', 'شركة القمة',
        'مجموعة البلوط', 'شركة الخط الفضي', 'شركة المدار', 'مؤسسة حجر الزاوية', 'شركة المياه الصافية'],
}
