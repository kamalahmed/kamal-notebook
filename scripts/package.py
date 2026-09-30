"""Build the single installable theme; no companion extension is required."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED
import hashlib

root = Path(__file__).resolve().parents[1]
source = root / 'theme'
out = root / 'dist'
out.mkdir(exist_ok=True)
package = out / 'kamal-notebook-theme.zip'
exclude = {'assets/notebook/code-view.src.js'}
with ZipFile(package, 'w', ZIP_DEFLATED, compresslevel=9) as archive:
    for path in sorted(source.rglob('*')):
        relative = path.relative_to(source).as_posix()
        if path.is_file() and relative not in exclude and not path.name.startswith('.'):
            archive.write(path, f'kamal-notebook/{relative}')
with ZipFile(package) as archive:
    assert archive.testzip() is None
    for required in ['style.css', 'functions.php', 'includes/notebook.php', 'includes/notebook/demo-import.php', 'assets/notebook/code-view.js', 'blocks/code/block.json', 'cloudflare.txt']:
        assert f'kamal-notebook/{required}' in archive.namelist(), required
checksum = hashlib.sha256(package.read_bytes()).hexdigest()
(package.with_suffix('.zip.sha256')).write_text(f'{checksum}  {package.name}\n')
print(f'{package.name}: {package.stat().st_size} bytes; single theme, all built-in features included')
