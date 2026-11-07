#!/usr/bin/env python3
"""Write the hand-made specs to specs/json/<id>.json:  python3 make.py re_office re_landlord ..."""
import json, os, sys, importlib
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
os.makedirs(os.path.join(os.path.dirname(os.path.abspath(__file__)), 'json'), exist_ok=True)
for name in sys.argv[1:]:
    spec = importlib.import_module(name).SPEC
    p = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'json', spec['id'] + '.json')
    json.dump(spec, open(p, 'w', encoding='utf-8'), ensure_ascii=False, indent=0)
    print(p)
