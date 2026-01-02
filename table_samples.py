import sqlite3, os, sys
p = os.path.join(os.path.dirname(__file__), 'ecommerce.db')
print('DB:', p)
if not os.path.exists(p):
    print('ERROR: ecommerce.db not found')
    sys.exit(2)
con = sqlite3.connect(p)
con.row_factory = sqlite3.Row
cur = con.cursor()
cur.execute("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name;")
tables = [r[0] for r in cur.fetchall()]
if not tables:
    print('No user tables found')
    sys.exit(0)
for t in tables:
    print('\n--- Table:', t)
    try:
        c = con.execute(f'SELECT COUNT(*) as cnt FROM "{t}"')
        cnt = c.fetchone()['cnt']
        print('Rows:', cnt)
    except Exception as e:
        print('Count error:', e)
        continue
    try:
        c = con.execute(f'SELECT * FROM "{t}" LIMIT 5')
        rows = c.fetchall()
        cols = [d[0] for d in c.description] if c.description else []
        if rows:
            print('Columns:', ', '.join(cols))
            for r in rows:
                vals = [repr(r[col]) for col in cols]
                print('  ', ', '.join(vals))
        else:
            print('No sample rows')
    except Exception as e:
        print('Sample error:', e)
con.close()
