#!/usr/bin/env bash
# Install a real Joomla, enable this plugin, and read the tag out of the served HTML.
#
# 🔴 This is the check that matters, and reading the code is not a substitute.
# Written from Joomla's own source, this plugin still shipped
# `script.js?9b26ab` on its first run, because `WebAssetItem::$version` is
# declared `protected $version = 'auto'` — the DEFAULT is 'auto', not empty, so
# registering an asset with no version option still gets you one. The tracker
# would have kept working (it finds itself by an `src*=` substring), while every
# Joomla site in the world requested a distinct URL from our CDN. Nothing but a
# rendered page shows that.
#
#   ./scripts/verify.sh 6.1.3
#   ./scripts/verify.sh 5.4.8
#
# Needs php, mariadb/mysql and curl on PATH, and a MariaDB reachable at
# $DB_HOST:$DB_PORT with a passwordless root (override via env).
#
# Exits non-zero if any case does not match.

set -uo pipefail

VERSION="${1:-6.1.3}"
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WORK="${JOOMLA_WORK:-${TMPDIR:-/tmp}/pulse-joomla-verify}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-13306}"
DB_USER="${DB_USER:-root}"
HTTP_PORT="${HTTP_PORT:-18080}"
DB_NAME="joomla_$(echo "$VERSION" | tr -d '.')"
SITE="$WORK/$VERSION"
PREFIX="fx_"

for tool in php curl unzip; do
  command -v "$tool" >/dev/null || { echo "REFUSING: $tool is not on PATH"; exit 1; }
done
MYSQL=$(command -v mariadb || command -v mysql) || { echo "REFUSING: no mariadb/mysql client"; exit 1; }
db() { "$MYSQL" -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$@" 2>/dev/null; }
db -e "SELECT 1" >/dev/null || { echo "REFUSING: no database at $DB_HOST:$DB_PORT"; exit 1; }

mkdir -p "$WORK"

# ── Download and unpack ──────────────────────────────────────────────────────
ZIP="$WORK/joomla-$VERSION.zip"
if [ ! -f "$ZIP" ]; then
  echo "==> fetching Joomla $VERSION"
  URL=$(curl -s "https://api.github.com/repos/joomla/joomla-cms/releases/tags/$VERSION" \
    | python3 -c "
import sys,json
for a in json.load(sys.stdin).get('assets',[]):
    if a['name'].endswith('Stable-Full_Package.zip'):
        print(a['browser_download_url']); break
")
  [ -n "$URL" ] || { echo "REFUSING: no Full_Package asset for $VERSION"; exit 1; }
  curl -sL "$URL" -o "$ZIP"
fi

if [ ! -f "$SITE/configuration.php" ]; then
  echo "==> installing Joomla $VERSION into $SITE"
  rm -rf "$SITE"; mkdir -p "$SITE"
  unzip -q "$ZIP" -d "$SITE"
  # Generated, not hard-coded. This site is a throwaway in a temp directory and
  # nobody ever logs into it — but a literal password in a public repository is
  # a thing somebody eventually reuses.
  ADMIN_PASS="fixture-$(LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 24)"
  db -e "DROP DATABASE IF EXISTS $DB_NAME; CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4;"
  ( cd "$SITE" && php installation/joomla.php install \
      --site-name "Pulse Fixture" \
      --admin-user Admin --admin-username adminuser --admin-password "$ADMIN_PASS" \
      --admin-email admin@example.com \
      --db-type mysqli --db-host "$DB_HOST:$DB_PORT" --db-user "$DB_USER" --db-pass "" \
      --db-name "$DB_NAME" --db-prefix "$PREFIX" -n ) | tail -2
fi

# ── Deploy the plugin the way an install would ───────────────────────────────
echo "==> deploying the plugin"
P="$SITE/plugins/system/pulseanalytics"
rm -rf "$P"; mkdir -p "$P"
cp -R "$PLUGIN_DIR/services" "$PLUGIN_DIR/src" "$PLUGIN_DIR/pulseanalytics.xml" "$P/"
mkdir -p "$SITE/administrator/language/en-GB"
cp "$PLUGIN_DIR/language/en-GB/"*.ini "$SITE/administrator/language/en-GB/"

# ⚠️ custom_data has no default in #__extensions; omitting it is an INSERT error,
# not a NULL. Found the hard way.
db "$DB_NAME" -e "
DELETE FROM ${PREFIX}extensions WHERE element='pulseanalytics';
INSERT INTO ${PREFIX}extensions
  (package_id, name, type, element, folder, client_id, enabled, access, protected,
   locked, manifest_cache, params, custom_data, checked_out, checked_out_time, ordering, state)
VALUES
  (0, 'plg_system_pulseanalytics', 'plugin', 'pulseanalytics', 'system', 0, 1, 1, 0,
   0, '{}', '{}', '', NULL, NULL, 0, 0);"

# ── Serve ────────────────────────────────────────────────────────────────────
php -S "127.0.0.1:$HTTP_PORT" -t "$SITE" > "$WORK/php-$VERSION.log" 2>&1 &
SERVER=$!
trap 'kill $SERVER 2>/dev/null' EXIT
for _ in $(seq 1 20); do
  curl -sf -o /dev/null "http://127.0.0.1:$HTTP_PORT/" && break
  sleep 0.5
done

FAILURES=0
CORE='https://js\.ciphera\.net/script\.js'
COMP='https://js\.ciphera\.net/script\.interactions\.js'

# $1 label, $2 params JSON, $3 must-match ERE, $4 must-NOT-match ERE (may be empty)
check() {
  db "$DB_NAME" -e "UPDATE ${PREFIX}extensions SET params='$2' WHERE element='pulseanalytics';"
  local html tags
  html=$(curl -s "http://127.0.0.1:$HTTP_PORT/")
  tags=$(printf '%s' "$html" | grep -oE '<script[^>]*ciphera[^>]*></script>' | tr '\n' ' ')
  if printf '%s' "$tags" | grep -qE "$3"; then
    echo "    ✅ $1"
  else
    echo "    ❌ $1"
    echo "       expected to match: $3"
    echo "       got: ${tags:-<nothing>}"
    FAILURES=$((FAILURES + 1))
  fi
  if [ -n "${4:-}" ] && printf '%s' "$tags" | grep -qE "$4"; then
    echo "    ❌ $1 — unexpectedly present: $4"
    echo "       got: $tags"
    FAILURES=$((FAILURES + 1))
  fi
}

echo "==> checking the served HTML"

# 🔴 The must-NOT-match argument `script\.js\?` is the whole point of this first
# case: it asserts NO cache-busting query on our CDN URL. It is the assertion
# that would have caught `script.js?9b26ab`. Do not relax it.
check "domain only, and NO ?version on the CDN url" \
  '{"domain":"example.com","companion":"0","api":""}' \
  "<script src=\"$CORE\" defer data-domain=\"example\.com\"></script>" \
  "script\.js\?"

check "domain + api + companion, companion carries no domain" \
  '{"domain":"chosen.dev","companion":"1","api":"https://proxy.example.com/"}' \
  "<script src=\"$CORE\" defer data-domain=\"chosen\.dev\" data-api=\"https://proxy\.example\.com\"></script> <script src=\"$COMP\" defer></script>" \
  ""

check "no domain configured means auto-detect, not a broken attribute" \
  '{"domain":"","companion":"0","api":""}' \
  "^<script src=\"$CORE\" defer></script> \$" \
  "data-domain"

check "an invalid domain is dropped, never injected" \
  '{"domain":"bad\\" onload=\\"x","companion":"0","api":""}' \
  "^<script src=\"$CORE\" defer></script> \$" \
  "onload"

# The administrator is not the site's audience and must carry nothing.
db "$DB_NAME" -e "UPDATE ${PREFIX}extensions SET params='{\"domain\":\"example.com\",\"companion\":\"1\",\"api\":\"\"}' WHERE element='pulseanalytics';"
ADMIN_HITS=$(curl -s "http://127.0.0.1:$HTTP_PORT/administrator/" | grep -c 'ciphera')
if [ "$ADMIN_HITS" -eq 0 ]; then
  echo "    ✅ the administrator carries no tag"
else
  echo "    ❌ the administrator carries $ADMIN_HITS reference(s) to ciphera"
  FAILURES=$((FAILURES + 1))
fi

echo
if [ "$FAILURES" -eq 0 ]; then
  echo "Joomla $VERSION: ALL CHECKS PASSED"
else
  echo "Joomla $VERSION: $FAILURES FAILURE(S)"
fi
exit "$FAILURES"
