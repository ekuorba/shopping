#!/usr/bin/env python3
import re, os, sys, sqlite3
here = os.path.dirname(os.path.abspath(__file__))
dump_in = os.path.join(here, 'ecommerce_dump_fixed.sql')
dump_out = os.path.join(here, 'ecommerce_dump_fixed2.sql')
if not os.path.exists(dump_in):
    print('Input dump not found:', dump_in); sys.exit(2)
with open(dump_in, 'r', encoding='utf-8') as f:
    s = f.read()
# insert DROP TABLE IF EXISTS before each CREATE TABLE
s2 = re.sub(r"CREATE TABLE\s+(\w+)", lambda m: f"DROP TABLE IF EXISTS {m.group(1)};\nCREATE TABLE {m.group(1)}", s, flags=re.IGNORECASE)
# wrap with pragmas
header = "PRAGMA foreign_keys=OFF;\nBEGIN TRANSACTION;\n"
footer = "COMMIT;\nPRAGMA foreign_keys=ON;\n"
# remove existing BEGIN/COMMIT if present to avoid duplicates
s2 = re.sub(r"BEGIN TRANSACTION;", '', s2, flags=re.IGNORECASE)
s2 = re.sub(r"COMMIT;", '', s2, flags=re.IGNORECASE)
out = header + s2.strip() + '\n' + footer
with open(dump_out, 'w', encoding='utf-8') as f:
    f.write(out)
print('Wrote', dump_out)
# validate by loading into temp DB
tmp = os.path.join(here, 'tmp_fixed2.db')
if os.path.exists(tmp): os.remove(tmp)
con = sqlite3.connect(tmp)
try:
    con.executescript(out)
    cur = con.execute('PRAGMA integrity_check;')
    rows = [r[0] for r in cur]
    print('PRAGMA integrity_check ->', rows)
except Exception as e:
    print('ERROR loading dump:', e)
    con.close()
    if os.path.exists(tmp): os.remove(tmp)
    sys.exit(3)
con.close()
if os.path.exists(tmp): os.remove(tmp)
print('Validated and removed temp DB')
