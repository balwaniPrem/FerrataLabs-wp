#!/usr/bin/env bash
# Fetch the tracked paths from the live server into site/.
# The server is authoritative: this overwrites local files.
set -euo pipefail
cd "$(dirname "$0")/.."
CONF="../ferratalabs.sftp"
[ -f "$CONF" ] || { echo "missing $CONF"; exit 1; }
# shellcheck disable=SC1090
. "$CONF"
[ -n "${SFTP_PASS:-}" ] || { echo "SFTP_PASS empty in $CONF"; exit 1; }
export SFTP_HOST SFTP_PORT SFTP_USER SFTP_PASS

ROOT=web/wp-live
mkdir -p site/wp-content/themes site/wp-content/mu-plugins
export SFTP_CMDS="lcd site
get $ROOT/robots.txt
lcd wp-content/themes
get -r $ROOT/wp-content/themes/ferrata-labs
lcd ../mu-plugins
get $ROOT/wp-content/mu-plugins/tenweb-init.php"
expect scripts/_sftp.exp 2>&1 | grep -vE "^spawn|Warning: Permanently|password:|^sftp> $" | tail -20
echo
echo "pulled. review with: git status"
