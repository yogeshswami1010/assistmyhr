# Backblaze B2 automatic backups

These Linux/systemd files back up the VPS's entire MariaDB instance and the ATS
project (including `.env`, encryption key, uploaded CVs and tenant storage).
Backups run hourly, encrypted using Restic. Integrity checks run weekly. Failed
commands stop the job; systemd records failure. No Laravel queue or scheduler is
required. This is not active until installed and a first backup succeeds.

Create a private B2 bucket dedicated to this VPS. Create a bucket-restricted
read/write application key (not the master key); copy the bucket's exact S3
endpoint. Enter credentials on the VPS only. B2 Computer Backup is a different
product; this setup needs B2 Cloud Storage. Do not configure lifecycle rules that
expire active Restic objects. Store the encryption password in a password manager
outside the VPS: without it restoration is impossible.

## Install on the existing Ubuntu/CyberPanel VPS as root

```bash
cd /home/assistmyhr.com/public_html/ats
sudo -u assis2045 git pull origin main
apt-get update
apt-get install -y restic mariadb-client
install -m 700 ops/backup/assistmyhr-backup.sh /usr/local/sbin/assistmyhr-backup
install -m 600 ops/backup/backup.env.example /etc/assistmyhr-backup.env
nano /etc/assistmyhr-backup.env
```

Replace repository, B2 key ID and application key. For example, repository format:
`s3:https://s3.us-west-004.backblazeb2.com/YOUR-BUCKET/assistmyhr`.
Use your own bucket endpoint, not this example region. If `SAAS_STORAGE_ROOT` is
outside the ATS folder, set `EXTRA_STORAGE_PATH` to that directory. Review all
other persistent external upload locations and include them before relying on
this backup. Existing remote S3 CV storage needs separate backup/versioning.

```bash
umask 077
openssl rand -base64 48 > /etc/assistmyhr-backup.password
chmod 600 /etc/assistmyhr-backup.password
```

Run password generation **once only**, then securely save that password outside
the VPS. Do not regenerate it when updating the script.

```bash
mariadb --user=root --protocol=socket -e 'SELECT CURRENT_USER();'
/usr/local/sbin/assistmyhr-backup init
/usr/local/sbin/assistmyhr-backup backup
/usr/local/sbin/assistmyhr-backup snapshots
/usr/local/sbin/assistmyhr-backup check
```

If root socket authentication fails, stop and configure a dedicated backup MySQL
option file; do not use the ATS application's database user or paste passwords
into commands. Initialization is only for a new empty Restic repository.

After successful first backup, install the timers:

```bash
install -m 644 ops/backup/assistmyhr-backup.service ops/backup/assistmyhr-backup.timer ops/backup/assistmyhr-backup-check.service ops/backup/assistmyhr-backup-check.timer /etc/systemd/system/
systemctl daemon-reload
systemctl enable --now assistmyhr-backup.timer assistmyhr-backup-check.timer
systemctl list-timers 'assistmyhr-backup*'
journalctl -u assistmyhr-backup.service -n 50 --no-pager
cat /var/lib/assistmyhr-backup/last-success
```

Timers use the VPS timezone. Hourly backups provide approximately one-hour
recovery gaps only when each run succeeds; long-running jobs delay subsequent
ones. Monitor systemd failures and stale last-success externally. Email failure
notifications require separate monitoring configuration.

## Restore test before relying on backups

On a separate recovery server with the repository credentials and encryption
password, export variables from the root-only configuration, then:

```bash
set -a
source /etc/assistmyhr-backup.env
set +a
restic snapshots --tag assistmyhr
restic restore SNAPSHOT_ID --target /srv/assistmyhr-restore-test
```

Use a snapshot ID containing the intended successful backup. Verify files and
`.env`; import `all-databases.sql.gz` into an isolated disposable MariaDB server,
then verify client counts and CV access. Never import this full-server SQL into
the live database as a test: it includes system databases and other sites.

The dump is transactionally consistent for InnoDB data; coordinate deployments
and schema changes outside backup windows. Database and file copies are not a
single atomic snapshot, so upload/deletion activity may require reconciliation.
Plaintext SQL is temporarily stored in a root-only directory then removed.

## Retention

No automatic deletion is enabled initially, preserving recovery points. After a
successful restore test, review retention with a dry run using the exported
Restic variables:

```bash
restic forget --tag assistmyhr --keep-hourly 48 --keep-daily 30 --keep-weekly 12 --keep-monthly 12 --dry-run
```

Apply a reviewed retention policy separately. Monitor B2 storage costs. A VPS
with read/write/delete credentials can delete its backup repository if compromised;
keep a separate recovery copy or use a designed append-only backup service with
separate retention credentials for stronger protection. Enabling B2 Object Lock
without planning repository lock/prune compatibility can break backups.
