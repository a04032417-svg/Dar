# -*- coding: utf-8 -*-
"""Lists shared by the five workbooks of the Dar Real Estate Pro Suite."""
from dsl import LST

PTYPE = LST('Property type', 'نوع العقار',
            'Apartment;Villa;Townhouse;Office;Retail shop;Land;Warehouse;Building',
            'شقة;فيلا;تاون هاوس;مكتب;محل تجاري;أرض;مستودع;عمارة')
PURPOSE = LST('Purpose', 'الغرض', 'Sale;Rent', 'بيع;إيجار', '#2E86AB;#F2A541')
DISTRICT = LST('District', 'الحي',
               'North District;South District;East District;West District;Downtown;Waterfront;Business Bay;Garden City',
               'حي الشمال;حي الجنوب;حي الشرق;حي الغرب;وسط المدينة;الواجهة البحرية;حي الأعمال;حي الحدائق')
PAYST = LST('Payment status', 'حالة الدفع', 'Paid;Partly paid;Unpaid;Overdue', 'مدفوع;مدفوع جزئياً;غير مدفوع;متأخر',
            '#27AE60;#F39C12;#7F8C8D;#C0392B')
PAYM = LST('Payment method', 'طريقة الدفع', 'Bank transfer;Card;Cash;Cheque;Online', 'تحويل بنكي;بطاقة;نقداً;شيك;دفع إلكتروني')
PRIORITY = LST('Priority', 'الأولوية', 'Urgent;High;Normal;Low', 'عاجل;عالي;عادي;منخفض', '#C0392B;#E67E22;#2E86AB;#7F8C8D')
TSTATUS = LST('Task status', 'حالة المهمة', 'To do;In progress;Done', 'للتنفيذ;قيد التنفيذ;منجز', '#7F8C8D;#F39C12;#27AE60')
YN = LST('Yes / No', 'نعم / لا', 'Yes;No', 'نعم;لا')
MCAT = LST('Maintenance category', 'فئة الصيانة',
           'Plumbing;Electrical;AC / HVAC;Painting;Carpentry;Appliances;Cleaning;Pest control;Elevator;Other',
           'سباكة;كهرباء;تكييف;دهان;نجارة;أجهزة;نظافة;مكافحة حشرات;مصاعد;أخرى')
CONDITION = LST('Condition', 'الحالة الفنية', 'Excellent;Good;Fair;Poor', 'ممتازة;جيدة;مقبولة;سيئة', '#27AE60;#2E86AB;#F39C12;#C0392B')
