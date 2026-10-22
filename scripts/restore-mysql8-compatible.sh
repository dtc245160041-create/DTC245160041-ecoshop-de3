#!/usr/bin/env bash
# Restore previous EcoShop MariaDB dump to NEW MySQL 8 lab, normalizing ONLY
# MariaDB UCA1400 collation incompatible with MySQL 8.0.
# Never change the original .sql, old containers, source volume or old database.
# Refuse to proceed if the NEW lab database has any tables (partial import).
set -euo pipefail
cd "$(dirname "$0")/.."
sql="${1:-/home/hoc/ecoshop-wordpress-recovered.sql}"
src="${2:-wordpress_wp_data}"
[[ -s "$sql" ]] || { echo "SOURCE_SQL_MISSING: $sql" >&2; exit 1; }
[[ -f .env ]] || { echo 'MISSING_ENV_FILE: .env' >&2; exit 1; }

echo 'Checking NEW lab MySQL table count; this command does not delete anything...'
tablecount="$(sudo docker compose exec -T mysql sh -c 'mysql --batch --skip-column-names -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()"' | tr -d '[:space:]')"
[[ "$tablecount" =~ ^[0-9]+$ ]] || { echo 'CANNOT_READ_LAB_TABLE_COUNT' >&2; exit 1; }
echo "LAB_TABLE_COUNT: $tablecount"
if [[ "$tablecount" != 0 ]]; then
  echo "STOP_PARTIAL_IMPORT: NEW lab MySQL contains $tablecount tables. Do not repeat SQL import or drop anything." >&2
  echo 'Send the table list for safe reconciliation: sudo docker compose exec -T mysql sh -c '\''mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -e "SHOW TABLES;"'\''' >&2
  exit 1
fi

# Explicitly reject other UCA collations until individually reviewed.
unsupported="$(grep -oE 'utf8mb4_uca[0-9]+_[a-z0-9_]+' "$sql" | sort -u | grep -v '^utf8mb4_uca1400_ai_ci$' || true)"
if [[ -n "$unsupported" ]]; then
  echo "STOP_OTHER_MARIADB_COLLATIONS: $unsupported" >&2
  exit 1
fi
if ! LC_ALL=C grep -q 'utf8mb4_uca1400_ai_ci' "$sql"; then
  echo 'No known MariaDB UCA1400 collation in source. Do not perform a blind import.' >&2
  exit 1
fi

# mktemp is created with 0600, outside Git repository; do not publish .sql.
umask 077
compatible="$(mktemp "$HOME/ecoshop-mysql8-compatible-XXXXXXXX.sql")"
cleanup_on_error() {
  if [[ "${complete:-0}" != 1 ]]; then
    echo "Converted private backup kept for inspection: $compatible" >&2
    echo 'The ORIGINAL SQL backup and Docker volumes have not been deleted.' >&2
  fi
}
trap cleanup_on_error EXIT

python3 - "$sql" "$compatible" <<'PY'
import sys
with open(sys.argv[1], 'rb') as f:
    source=f.read()
old=b'utf8mb4_uca1400_ai_ci'
new=b'utf8mb4_unicode_ci'
count=source.count(old)
if not count:
    sys.exit('Source collation not found, aborted')
with open(sys.argv[2], 'wb') as f:
    f.write(source.replace(old,new))
print('COLLATION_REPLACEMENTS: %d' % count)
PY
chmod 600 "$compatible"
echo 'Converted file created privately; original SQL is untouched.'
echo 'Note: MySQL8 utf8mb4_unicode_ci has different comparison rules from MariaDB UCA1400.'

# Existing safe restore script independently rechecks SQL file, source volume,
# destination table emptiness and copies wp-content with source mounted read-only.
bash scripts/restore-lab.sh "$compatible" "$src"
complete=1
echo 'MYSQL8_COMPAT_RESTORE_DONE: check WordPress :18080 and the 12 expected tables.'
