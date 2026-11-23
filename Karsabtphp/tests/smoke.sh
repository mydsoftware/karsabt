#!/usr/bin/env bash
# Smoke tests for Karsabt PHP. Requires PHP CLI and curl.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PORT="${KARSABT_TEST_PORT:-8127}"
LOG="${TMPDIR:-/tmp}/karsabt-smoke-php.log"
php -S "127.0.0.1:${PORT}" -t "$ROOT" >"$LOG" 2>&1 &
SERVER_PID=$!
cleanup(){ kill "$SERVER_PID" >/dev/null 2>&1 || true; }
trap cleanup EXIT
for i in $(seq 1 30); do
  if curl -fsS "http://127.0.0.1:${PORT}/" >/dev/null 2>&1; then break; fi
  sleep 0.2
done
fail=0
check(){
  local path="$1" expected="$2" label="$3"
  local code body
  body="$(mktemp)"
  code="$(curl -sS -o "$body" -w '%{http_code}' "http://127.0.0.1:${PORT}${path}" || true)"
  if [[ "$code" == "$expected" ]]; then echo "PASS $label ($code)"; else echo "FAIL $label expected=$expected got=$code"; fail=1; fi
  rm -f "$body"
}
check "/" 200 "Homepage"
for page in company brand changes licenses limited private fees contact; do check "/?page=${page}" 200 "Service page: ${page}"; done
check "/articles-index.php" 200 "Articles index (direct PHP route)"
check "/article.php?slug=start-company" 200 "Article detail (direct PHP route)"
check "/robots.txt" 200 "Robots"
check "/sitemap.php" 200 "Dynamic sitemap endpoint"
if [[ "$fail" -ne 0 ]]; then exit 1; fi
echo "All smoke checks passed. Friendly Apache rewrite URLs, including /sitemap.xml, and .htaccess rules must be tested on Apache/cPanel separately."
