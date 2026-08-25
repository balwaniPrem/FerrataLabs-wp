#!/usr/bin/env bash
# Upload one file to the live server. Path is relative to site/.
#   ./scripts/push.sh robots.txt
#   ./scripts/push.sh wp-content/themes/ferrata-labs/functions.php
# Deliberately one file at a time: this writes to production.
set -euo pipefail
cd "$(dirname "$0")/.."
REL="${1:?usage: push.sh <path-relative-to-site/>}"
[ -f "site/$REL" ] || { echo "no such file: site/$REL"; exit 1; }
CONF="../ferratalabs.sftp"
# shellcheck disable=SC1090
. "$CONF"
export SFTP_HOST SFTP_PORT SFTP_USER SFTP_PASS
ROOT=web/wp-live
echo "UPLOAD  site/$REL  ->  $ROOT/$REL"
read -r -p "this writes to the live site. continue? [y/N] " ok
[ "$ok" = "y" ] || { echo "aborted"; exit 1; }
export SFTP_CMDS="put site/$REL $ROOT/$REL"
expect scripts/_sftp.exp 2>&1 | grep -vE "^spawn|Warning: Permanently|password:" | tail -8
