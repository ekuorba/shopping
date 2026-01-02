import sqlite3, os, sys
p = os.path.join(os.path.dirname(__file__), 'ecommerce.db')
print('DB:', p)
if not os.path.exists(p):
    print('ERROR: ecommerce.db not found')
    sys.exit(2)
try:
    con = sqlite3.connect(p)
    cur = con.execute("SELECT type, name FROM sqlite_master WHERE type IN ('table','view') ORDER BY name;")
    rows = cur.fetchall()
    if not rows:
        print('No tables or views found')
    else:
        for t in rows:
            print(f"{t[0]} : {t[1]}")
    con.close()
except Exception as e:
    print('ERROR:', e)
    sys.exit(1)
