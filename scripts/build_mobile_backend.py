#!/usr/bin/env python3
"""Package committed mobile-backend source and locked production dependencies."""
import hashlib
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import zipfile


def run(*args, cwd):
    subprocess.run(args, cwd=cwd, check=True)


def git(*args):
    return subprocess.check_output(['git', *args], cwd=ROOT, text=True).strip()


ROOT = Path(__file__).resolve().parents[1]
if git('branch', '--show-current') != 'mobile-backend':
    sys.exit('Build from the mobile-backend branch.')
if git('status', '--porcelain', '--untracked-files=no'):
    sys.exit('Commit tracked changes first; the archive is built from HEAD.')
revision = git('rev-parse', 'HEAD')
output = Path(sys.argv[1] if len(sys.argv) > 1 else
              '/tmp/ktbus-backend-builds').resolve()
output.mkdir(parents=True, exist_ok=True)
archive = output / ('ktbus-mobile-backend-' + revision[:12] + '.zip')
if archive.exists():
    sys.exit('Archive already exists: ' + str(archive))

with tempfile.TemporaryDirectory(prefix='ktbus-backend-') as work:
    work = Path(work)
    source = work / 'source.zip'
    run('git', 'archive', '--format=zip', '--output=' + str(source),
        revision, cwd=ROOT)
    stage = work / 'backend'
    with zipfile.ZipFile(source) as files:
        files.extractall(stage)
    for folder in ('bootstrap/cache', 'storage/app/public',
                   'storage/framework/cache/data', 'storage/framework/sessions',
                   'storage/framework/views', 'storage/logs'):
        (stage / folder).mkdir(parents=True, exist_ok=True)
    run('composer', 'install', '--no-dev', '--prefer-dist', '--no-interaction',
        '--optimize-autoloader', cwd=stage)
    run('composer', 'check-platform-reqs', '--no-dev', cwd=stage)
    (stage / 'BUILD_REVISION').write_text(revision + '\n')
    # Composer creates package caches; let the deployment regenerate all caches.
    for folder in ('bootstrap/cache', 'storage'):
        for file in (stage / folder).rglob('*'):
            if file.is_file():
                file.unlink()
    temporary = work / 'build.zip'
    with zipfile.ZipFile(temporary, 'w', zipfile.ZIP_DEFLATED) as bundle:
        for file in sorted(stage.rglob('*')):
            bundle.write(file, str(file.relative_to(stage)))
    # Copy only a complete archive, without overwriting an existing build.
    with temporary.open('rb') as src, archive.open('xb') as dst:
        import shutil
        shutil.copyfileobj(src, dst)

digest = hashlib.sha256(archive.read_bytes()).hexdigest()
archive.with_suffix('.zip.sha256').write_text(digest + '  ' + archive.name + '\n')
print('Built ' + str(archive))
print('SHA256 ' + digest)
