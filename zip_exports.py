import os, zipfile
here = os.path.dirname(os.path.abspath(__file__))
out = os.path.join(here, 'exports.zip')
with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED) as z:
    expdir = os.path.join(here, 'exports')
    if os.path.isdir(expdir):
        for fn in sorted(os.listdir(expdir)):
            path = os.path.join(expdir, fn)
            if os.path.isfile(path):
                z.write(path, arcname=os.path.join('exports', fn))
    dump = os.path.join(here, 'ecommerce_dump_from_db.sql')
    if os.path.isfile(dump):
        z.write(dump, arcname=os.path.basename(dump))
print('Wrote', out)
print('Contents:')
with zipfile.ZipFile(out) as z:
    for info in z.infolist():
        print(info.filename, info.file_size)
