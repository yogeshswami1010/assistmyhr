#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
[[ $EUID -eq 0 ]] || { echo 'Run as root.' >&2; exit 1; }
config=/etc/assistmyhr-backup.env
[[ -f $config ]] || { echo 'Missing /etc/assistmyhr-backup.env' >&2; exit 1; }
[[ $(stat -c %u "$config") == 0 && $(stat -c %a "$config") == 600 ]] || { echo 'Backup configuration must be root-owned with mode 600.' >&2; exit 1; }
set -a
source "$config"
set +a
: "${RESTIC_REPOSITORY:?Set backup repository}"
: "${RESTIC_PASSWORD_FILE:?Set encryption password file}"
: "${ATS_PATH:?Set ATS path}"
[[ -f "$RESTIC_PASSWORD_FILE" && $(stat -c %u "$RESTIC_PASSWORD_FILE") == 0 && $(stat -c %a "$RESTIC_PASSWORD_FILE") == 600 ]] || { echo 'Password file must be root-owned with mode 600.' >&2; exit 1; }
[[ "$RESTIC_REPOSITORY" == s3:https://*.backblazeb2.com/* ]] || { echo 'Use the HTTPS Backblaze B2 S3 repository.' >&2; exit 1; }
export RESTIC_CACHE_DIR=/var/cache/assistmyhr-backup
mkdir -p /var/lib/assistmyhr-backup "$RESTIC_CACHE_DIR"
exec 9>/var/lib/assistmyhr-backup/run.lock
flock -n 9 || { echo 'Another backup operation is running.' >&2; exit 1; }
case "${1:-backup}" in
  init) restic init; exit ;;
  check) restic check --read-data-subset=5%; exit ;;
  snapshots) restic snapshots --tag assistmyhr; exit ;;
  backup) ;;
  *) echo 'Usage: assistmyhr-backup {init|backup|check|snapshots}' >&2; exit 1 ;;
esac
[[ -f "$ATS_PATH/artisan" && -f "$ATS_PATH/.env" ]] || { echo 'ATS path or .env missing.' >&2; exit 1; }
# Private staging outside the website; clean unfinished dumps after failures.
dump=/var/lib/assistmyhr-backup/all-databases.sql.gz
trap 'rm -f -- "$dump"' EXIT
trap 'echo "AssistMyHR backup failed; check journalctl -u assistmyhr-backup.service" >&2' ERR
paths=(/var/lib/assistmyhr-backup "$ATS_PATH")
if [[ -n ${EXTRA_STORAGE_PATH:-} ]]; then
  [[ -d "$EXTRA_STORAGE_PATH" ]] || { echo 'Extra storage path missing.' >&2; exit 1; }
  paths+=("$EXTRA_STORAGE_PATH")
fi
# Dump every database, including retained/deleted client databases and other sites.
# Uses MariaDB root socket authentication; no password in process arguments.
mariadb-dump --user=root --protocol=socket --all-databases --single-transaction --quick --routines --events --triggers --hex-blob | gzip -1 > "$dump"
gzip -t "$dump"
restic backup --tag assistmyhr --exclude "$ATS_PATH/.git" --exclude "$ATS_PATH/node_modules" --exclude "$ATS_PATH/storage/logs" --exclude "$ATS_PATH/storage/framework/cache" --exclude "$ATS_PATH/storage/framework/sessions" --exclude "$ATS_PATH/storage/framework/views" --exclude /var/lib/assistmyhr-backup/run.lock -- "${paths[@]}"
printf '%s\n' "$(date -u +%FT%TZ)" > /var/lib/assistmyhr-backup/last-success
# No automatic deletion: set retention only after a successful restore test.
