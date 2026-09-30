"""Run against Local WordPress: python3 tests/contact-security-concurrency.py.
Separate PHP processes contend on real SQL, with all mail/HTTP intercepted.
Each case uses its own cloned, disposable counter table, never live budgets.
"""
import concurrent.futures
import json
import pathlib
import subprocess
import time
import uuid

ROOT = pathlib.Path(__file__).resolve().parents[1]
WP = ['python3', str(ROOT / 'local/page-refresh/wp.py')]

def wp(*args):
    result = subprocess.run(WP + list(args), cwd=ROOT, check=True, text=True, capture_output=True)
    return result.stdout.strip()

results = {}
for case, maximum in [('duplicate', 1), ('email', 3), ('ip', 5), ('global', 4), ('attempt', 4)]:
    run = uuid.uuid4().hex[:12]
    setup = "if ('local' !== wp_get_environment_type()) throw new RuntimeException('Local only'); global $wpdb; $wpdb->query('CREATE TABLE ' . $wpdb->prefix . 'security_test_%s_knt_contact_limits LIKE ' . $wpdb->prefix . 'knt_contact_limits');" % run
    wp('eval', setup)
    try:
        start = str(time.time() + 3)
        def worker(index):
            return json.loads(wp('eval-file', str(ROOT / 'tests/contact-security-worker.php'), run, case, str(index), start))
        with concurrent.futures.ThreadPoolExecutor(max_workers=12) as executor:
            responses = list(executor.map(worker, range(24)))
        sent = sum(r['mail'] for r in responses)
        remote = sum(r['remote'] for r in responses)
        allowed = sum(r['status'] == ('captcha' if case == 'attempt' else 'sent') for r in responses)
        assert allowed == maximum, (case, responses)
        assert sent == (0 if case == 'attempt' else maximum), (case, responses)
        assert remote == (maximum if case == 'attempt' else 0), (case, responses)
        assert all(r['status'] in ('sent', 'limited', 'captcha') for r in responses), responses
        results[case] = {'workers': 24, 'accepted': allowed, 'intercepted_mail': sent, 'mocked_remote': remote}
    finally:
        wp('eval', "global $wpdb; $wpdb->query('DROP TABLE ' . $wpdb->prefix . 'security_test_%s_knt_contact_limits');" % run)
print(json.dumps(results, indent=2))
