#!/usr/bin/env python3
"""Keep one Android emulator current while developing this Flutter app."""
import argparse
import json
import os
from pathlib import Path
import queue
import shutil
import signal
import subprocess
import threading
import time

ROOT = Path(__file__).resolve().parents[1]


def snapshot():
    files = [ROOT / 'pubspec.yaml', ROOT / 'pubspec.lock', ROOT / 'dart_defines.local.json']
    for folder in ['lib', 'assets', 'android', 'ios']:
        files += [p for p in (ROOT / folder).rglob('*') if p.is_file()
                  and not any(x in p.parts for x in ['build', '.gradle', '.cxx', 'Pods', '.symlinks', 'ephemeral'])
                  and p.suffix in ['.dart', '.png', '.jpg', '.jpeg', '.webp', '.svg', '.ttf', '.otf', '.json', '.xml', '.kt', '.kts', '.swift', '.plist']]
    return {str(p.relative_to(ROOT)): (p.stat().st_mtime_ns, p.stat().st_size)
            for p in files if p.exists()}


def needs_rebuild(changed):
    return any(not p.startswith(('lib/', 'assets/')) for p in changed)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--device', default='emulator-5554')
    parser.add_argument('--flutter', default=shutil.which('flutter') or str(Path.home() / 'development/flutter/bin/flutter'))
    args = parser.parse_args()
    if not args.device.startswith('emulator-'):
        parser.error('This runner is for Android emulators only.')
    if not (ROOT / 'dart_defines.local.json').is_file():
        parser.error('Configure dart_defines.local.json before starting.')
    if subprocess.check_output(['git', 'branch', '--show-current'], cwd=ROOT, text=True).strip() != 'mobile-frontend':
        parser.error('Run only on mobile-frontend.')
    # Prevent two watchers from rebuilding the same emulator concurrently.
    import fcntl
    lock_path = ROOT / '.dart_tool' / ('autoupdate-' + args.device + '.lock')
    lock_path.parent.mkdir(exist_ok=True)
    lock = lock_path.open('w')
    try:
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
    except BlockingIOError:
        parser.error('An auto-update runner already owns this emulator.')
    events = queue.Queue()
    child = None
    app_id = None
    request_id = 0
    reload_id = None

    def start():
        process = subprocess.Popen([
            args.flutter, 'run', '--machine', '--debug', '-d', args.device,
            '--dart-define-from-file=dart_defines.local.json',
        ], cwd=ROOT, stdin=subprocess.PIPE, stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT, text=True, bufsize=1, start_new_session=True)
        def read():
            for line in process.stdout:
                events.put((process.pid, line.strip()))
        threading.Thread(target=read, daemon=True).start()
        print('Building and installing the latest emulator app…', flush=True)
        return process

    def send(method, params):
        nonlocal request_id
        request_id += 1
        child.stdin.write(json.dumps([{'id': request_id, 'method': method, 'params': params}]) + '\n')
        child.stdin.flush()
        return request_id

    def stop():
        if child is None or child.poll() is not None:
            return
        if app_id:
            send('app.detach', {'appId': app_id})
            try:
                child.wait(timeout=3)
                return
            except subprocess.TimeoutExpired:
                pass
        os.killpg(child.pid, signal.SIGTERM)
        child.wait(timeout=10)

    previous = snapshot()
    pending = set()
    changed_at = 0.0
    child = start()
    try:
        while True:
            current = snapshot()
            changed = {p for p in current.keys() | previous.keys() if current.get(p) != previous.get(p)}
            if changed:
                pending.update(changed)
                changed_at = time.monotonic()
                previous = current
            try:
                pid, line = events.get(timeout=.5)
                if pid != child.pid:
                    continue
                try:
                    messages = json.loads(line)
                except ValueError:
                    if line:
                        print(line, flush=True)
                    messages = []
                for message in messages if isinstance(messages, list) else []:
                    event = message.get('event')
                    params = message.get('params', {})
                    if event == 'app.started':
                        app_id = params['appId']
                        print('App ready. Saved changes will update this emulator automatically.', flush=True)
                    elif event == 'app.progress' and params.get('message'):
                        print(params['message'], flush=True)
                    elif message.get('id') == reload_id and reload_id is not None:
                        result = message.get('result', {})
                        succeeded = not message.get('error') and result.get('code') == 0
                        print('Emulator updated.' if succeeded else 'Reload failed; fix the code and save again.', flush=True)
                        reload_id = None
            except queue.Empty:
                pass
            if pending and time.monotonic() - changed_at >= 2 and reload_id is None:
                if child.poll() is not None or needs_rebuild(pending):
                    stop()
                    app_id = None
                    child = start()
                    pending.clear()
                elif app_id:
                    print('Updating saved changes: ' + ', '.join(sorted(pending)), flush=True)
                    reload_id = send('app.restart', {'appId': app_id, 'fullRestart': True, 'pause': False})
                    pending.clear()
    except KeyboardInterrupt:
        print('\nStopping the watcher. The installed app remains available.', flush=True)
    finally:
        stop()


if __name__ == '__main__':
    main()
