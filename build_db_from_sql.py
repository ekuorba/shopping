#!/usr/bin/env python3
import sqlite3, os, sys
here = os.path.dirname(os.path.abspath(__file__))
schema = os.path.join(here, 'schema.sql')
sample = os.path.join(here, 'sample_data.sql')
out = os.path.join(here, 'ecommerce_rebuilt.db')
if os.path.exists(out):
    os.remove(out)
con = sqlite3.connect(out)
cur = con.cursor()
if os.path.exists(schema):
    with open(schema, 'r', encoding='utf-8') as f:
        sql = f.read()
    try:
        cur.executescript(sql)
        print('Applied', schema)
    except Exception as e:
        print('Failed to apply schema:', e)
        con.close()
        sys.exit(2)
else:
    print('schema.sql not found; cannot build DB')
    con.close()
    sys.exit(3)
if os.path.exists(sample):
    with open(sample, 'r', encoding='utf-8') as f:
        sql = f.read()
    try:
        cur.executescript(sql)
        print('Applied', sample)
    except Exception as e:
        print('Failed to apply sample data:', e)
        con.close()
        sys.exit(4)
else:
    print('sample_data.sql not found; created DB with schema only')
con.commit()
con.close()
print('Created', out)
# verify integrity
con = sqlite3.connect(out)
try:
    cur = con.execute('PRAGMA integrity_check;')
    rows = [r[0] for r in cur]
    print('PRAGMA integrity_check ->', rows)
except Exception as e:
    print('Integrity check error:', e)
con.close()
