#!/bin/bash
# ==========================================
# Automated PostgreSQL Restore Script
# Centralized IT Helpdesk System
# ==========================================
set -euo pipefail

if [ -z "${1:-}" ]; then
  echo "Usage: $0 /path/to/backup_file.sql.gz"
  exit 1
fi

BACKUP_FILE="$1"

if [ ! -f "${BACKUP_FILE}" ]; then
  echo "Error: Backup file '${BACKUP_FILE}' does not exist."
  exit 1
fi

echo "=========================================="
echo "WARNING: Restoring will overwrite existing data in helpdesk_db!"
echo "Target backup: ${BACKUP_FILE}"
echo "=========================================="
read -p "Are you sure you want to proceed? (yes/no): " CONFIRM

if [ "${CONFIRM}" != "yes" ]; then
  echo "Restore aborted by user."
  exit 0
fi

echo "[$(date)] Terminating active database connections and dropping existing schema..."
docker compose -f compose.prod.yaml exec -T postgres psql -U "${DB_USERNAME:-helpdesk_user}" -d "${DB_DATABASE:-helpdesk_db}" -c "
  SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '${DB_DATABASE:-helpdesk_db}' AND pid <> pg_backend_pid();
  DROP SCHEMA public CASCADE;
  CREATE SCHEMA public;
"

echo "[$(date)] Restoring data from ${BACKUP_FILE}..."
gunzip -c "${BACKUP_FILE}" | docker compose -f compose.prod.yaml exec -T postgres psql -U "${DB_USERNAME:-helpdesk_user}" -d "${DB_DATABASE:-helpdesk_db}"

echo "[$(date)] Database restoration completed successfully."
