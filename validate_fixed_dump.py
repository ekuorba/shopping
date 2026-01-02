import sqlite3, os, sys
here = os.path.dirname(os.path.abspath(__file__))
dump = os.path.join(here, 'ecommerce_dump_fixed.sql')
if not os.path.exists(dump):
    print('dump not found'); sys.exit(2)
tmp = os.path.join(here, 'tmp_validate.db')
if os.path.exists(tmp): os.remove(tmp)
con = sqlite3.connect(tmp)
try:
    with open(dump, 'r', encoding='utf-8') as f:
        sql = f.read()
    con.executescript(sql)
    cur = con.execute('PRAGMA integrity_check;')
    rows = [r[0] for r in cur]
    print('PRAGMA integrity_check ->', rows)
except Exception as e:
    print('ERROR:', e)
finally:
    con.close()
    if os.path.exists(tmp): os.remove(tmp)
