# How Dar's AI teams write a template spec (compact form)

Every string that a buyer sees is given in English and Arabic. Keys are snake_case.

Field: "key|English|Arabic|type|extra"
- type: id | text:person | text:company | text:phone | text:email | text:note | text:code | text:Sample 1;Sample 2/مثال 1;مثال 2
  | date | date:PAST_DAYS,FUTURE_DAYS | date~OTHER_DATE_KEY:MIN-MAX (days after another date)
  | money:MIN-MAX/STEP | num:MIN-MAX | int:MIN-MAX | pct:0.01-0.3 | list:LIST_KEY | ref:MODULE_ID
  | calc:FORMAT:=EXCEL_FORMULA   (FORMAT money|num|int|pct|date|text)
- formula tokens: {key} same row; {@module.field|key} look up another register by the ID in column key;
  {#module.field} whole column of a register (for COUNTIF/SUMIFS); {L:list_key:N} item N of a list; {tx:English/Arabic} text;
  never use the | character inside a formula except in {@...|...}.
- extra (comma separated): databar | scale | scale_r | soon | overdue | seq | wts=5:3:1 | per=key:LOW:HIGH | eq=key
- the first field is always "id|... ID|رقم ...|id"; the second is usually the main date.

KPI: "English|Arabic|agg|field|where|fmt"  agg count|sum|avg|max|min|pct|ratio(field=a/b)  where "status=0" (list item index)  fmt money|pct|num

Module: {"id","g","en","ar","den","dar","prefix","rows":20-60,"fields":[...],"kpis":[4 KPIs],"date":"<date key>","val":"<number key>","agg":"sum|avg|count","by":"list_key_field,other","heat":"list_field:number_field:avg"}

List: "key": ["Name en","Name ar","item1;item2;...","بند1;بند2;...","#hex;#hex (optional colours)"]

Calculator: {"id","g","en","ar","den","dar","inputs":["key|English|Arabic|fmt|default"],"outputs":["key|English|Arabic|fmt|FORMULA_WITH_{keys}|1 (highlight)"],
 "table": {"title":["en","ar"],"rows":N,"cols":["key|English|Arabic|fmt|FIRST_ROW_FORMULA|NEXT_ROWS_FORMULA"],"while":"{n}<={term}","chart":["line","colkey"]}}
 table tokens: {n} row number, {c:col} same row, {p:col} previous row, plus input/output keys.

Checklist: {"id","g","en","ar","den","dar","steps":["Step en|Step ar|DAYS_FROM_START|Owner en|Owner ar"]}

Form: {"id","g","en","ar","module":"<module id>","blocks":[["h","TITLE","العنوان"],["kv","field1","field2"],["p","Text with {field}","نص مع {field}"],["sign","Party|الطرف","Party 2|الطرف 2"]]}

Dashboard: {"kpis":[["module_id",kpi_index] x8],"charts":[{"m":"module_id","by":"month|list_field","t":"column|bar|line|doughnut|area","a":"sum|avg|count","v":"field","n":["en","ar"]} x6]}

Store copy: {"store":{"en":{"name","subtitle","description","bullets":[...],"tags":[13],"seo_title","seo_description"},"ar":{...},"fr":{...},...}}
