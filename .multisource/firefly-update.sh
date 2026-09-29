#!/usr/bin/env bash
# firefly-update.sh - update a community-scripts Firefly III LXC from the
# firefly-iii-multisource fork instead of upstream.
#
# Install inside the LXC:
#   curl -fsSL https://raw.githubusercontent.com/Simon0Harms/firefly-iii-multisource/main/.multisource/firefly-update.sh \
#     -o /usr/local/bin/firefly-update && chmod +x /usr/local/bin/firefly-update
#   firefly-update --install-guard     # blocks the upstream 'update' command
#
# Usage: firefly-update [--check] [--tag vX.Y.Z-multisource] [--install-guard]
#
# License: GPL-3.0-or-later
set -Eeuo pipefail

REPO="Simon0Harms/firefly-iii-multisource"
APP_DIR="/opt/firefly"
BACKUP_ROOT="/opt/firefly-backups"
KEEP_BACKUPS=5
WEB_USER="www-data"

log()  { printf '\e[1;34m[firefly-update]\e[0m %s\n' "$*"; }
die()  { printf '\e[1;31m[firefly-update] ERROR:\e[0m %s\n' "$*" >&2; exit 1; }

[[ $EUID -eq 0 ]] || die "run as root"
command -v curl >/dev/null || die "curl missing"
command -v unzip >/dev/null || die "unzip missing (apt install unzip)"
command -v jq >/dev/null || die "jq missing (apt install jq)"

install_guard() {
  # The community-scripts 'update' pulls upstream Firefly III and runs
  # upgrade-database, which would unify all multi-source splits. Block it.
  local target
  target=$(command -v update || true)
  if [[ -n "$target" && ! -L "$target" && ! -e "$target.community" ]]; then
    mv "$target" "$target.community"
  fi
  cat > /usr/bin/update <<'EOF'
#!/usr/bin/env bash
echo "This LXC runs firefly-iii-multisource."
echo "The community 'update' would install upstream Firefly III and silently merge"
echo "split transactions with different source accounts. Use: firefly-update"
echo "(Original script kept as /usr/bin/update.community - do NOT use it for Firefly.)"
exit 1
EOF
  chmod +x /usr/bin/update
  log "guard installed: 'update' now refuses to run"
}

CHECK_ONLY=0; TAG=""
while [[ $# -gt 0 ]]; do
  case "$1" in
    --check) CHECK_ONLY=1 ;;
    --tag) TAG="$2"; shift ;;
    --install-guard) install_guard; exit 0 ;;
    *) die "unknown option $1" ;;
  esac
  shift
done

API="https://api.github.com/repos/$REPO/releases"
if [[ -n "$TAG" ]]; then REL_JSON=$(curl -fsSL "$API/tags/$TAG"); else REL_JSON=$(curl -fsSL "$API/latest"); fi
NEW=$(jq -r .tag_name <<<"$REL_JSON")
ZIP_URL=$(jq -r '.assets[] | select(.name|test("^FireflyIII-multisource-.*\\.zip$")) | .browser_download_url' <<<"$REL_JSON")
SHA_URL=$(jq -r '.assets[] | select(.name|test("\\.zip\\.sha256$")) | .browser_download_url' <<<"$REL_JSON")
[[ -n "$ZIP_URL" && "$ZIP_URL" == https://github.com/$REPO/* ]] || die "no release asset from $REPO found"

CUR=$(cat "$APP_DIR/MULTISOURCE_VERSION" 2>/dev/null || echo "upstream/unknown")
log "installed: $CUR   available: $NEW"
[[ $CHECK_ONLY -eq 1 ]] && exit 0
if [[ "$CUR" == "$NEW" ]]; then log "already up to date"; exit 0; fi

TMP=$(mktemp -d); trap 'rm -rf "$TMP"' EXIT
log "downloading $NEW"
curl -fsSL "$ZIP_URL" -o "$TMP/ff.zip"
curl -fsSL "$SHA_URL" -o "$TMP/ff.sha256"
(cd "$TMP" && [[ "$(awk '{print $1}' ff.sha256)" == "$(sha256sum ff.zip | awk '{print $1}')" ]]) || die "checksum mismatch"
mkdir "$TMP/new" && unzip -q "$TMP/ff.zip" -d "$TMP/new"
grep -q "firefly-iii-multisource" "$TMP/new/app/Validation/TransactionValidation.php" || die "archive does not contain the multisource patch - aborting"

# --- backup: database + .env + storage + old code -------------------------
TS=$(date +%Y%m%d-%H%M%S); BK="$BACKUP_ROOT/$TS"; mkdir -p "$BK"
set -a; source "$APP_DIR/.env"; set +a
case "${DB_CONNECTION:-mysql}" in
  mysql|mariadb) mysqldump --single-transaction -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" \
                   -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" | gzip > "$BK/db.sql.gz" ;;
  pgsql)         PGPASSWORD="$DB_PASSWORD" pg_dump -h "${DB_HOST:-127.0.0.1}" -p "${DB_PORT:-5432}" \
                   -U "$DB_USERNAME" "$DB_DATABASE" | gzip > "$BK/db.sql.gz" ;;
  sqlite)        cp "$APP_DIR/storage/database/database.sqlite" "$BK/" ;;
  *) die "unknown DB_CONNECTION ${DB_CONNECTION}" ;;
esac
tar -C "$(dirname "$APP_DIR")" -czf "$BK/firefly-app.tar.gz" "$(basename "$APP_DIR")"
log "backup written to $BK"
ls -1dt "$BACKUP_ROOT"/*/ 2>/dev/null | tail -n +$((KEEP_BACKUPS+1)) | xargs -r rm -rf

# --- swap code, keep .env and storage --------------------------------------
cp "$APP_DIR/.env" "$TMP/new/.env"
rm -rf "$TMP/new/storage" && cp -a "$APP_DIR/storage" "$TMP/new/storage"
# community-scripts installs the Data Importer inside the app dir - keep it.
[[ -d "$APP_DIR/dataimporter" ]] && cp -a "$APP_DIR/dataimporter" "$TMP/new/dataimporter"
mv "$APP_DIR" "$APP_DIR.old-$TS"
mv "$TMP/new" "$APP_DIR"
chown -R "$WEB_USER:$WEB_USER" "$APP_DIR"
chmod -R 775 "$APP_DIR/storage"

cd "$APP_DIR"
run() { log "php artisan $*"; runuser -u "$WEB_USER" -- php artisan "$@"; }
run cache:clear
run config:clear
run route:clear
run view:clear
run migrate --seed --force
run firefly-iii:upgrade-database
run firefly-iii:laravel-passport-keys
run storage:link || true
run optimize
systemctl reload apache2 || true

rm -rf "$APP_DIR.old-$TS"
log "updated to $NEW (rollback: restore $BK)"
