#!/bin/bash
# ==========================================
# Automated PostgreSQL Backup Script
# Centralized IT Helpdesk System
# ==========================================
set -euo pipefail

BACKUP_DIR="${BACKUP_DIR:-/var/backups/helpdesk}"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="${BACKUP_DIR}/helpdesk_db_${TIMESTAMP}.sql.gz"
RETENTION_DAYS=14

mkdir -p "${BACKUP_DIR}"

echo "[$(date)] Starting PostgreSQL backup to ${BACKUP_FILE}..."

# Execute pg_dump from within the postgres container to host file
docker compose -f compose.prod.yaml exec -T postgres pg_dump -U "${DB_USERNAME:-helpdesk_user}" "${DB_DATABASE:-helpdesk_db}" | gzip > "${BACKUP_FILE}"

# Secure file permissions (owner read/write only)
chmod 600 "${BACKUP_FILE}"

echo "[$(date)] Backup completed successfully. Size: $(du -h "${BACKUP_FILE}" | cut -f1)"

# Prune backups older than RETENTION_DAYS
echo "[$(date)] Cleaning backups older than ${RETENTION_DAYS} days..."
find "${BACKUP_DIR}" -name "helpdesk_db_*.sql.gz" -mtime +"${RETENTION_DAYS}" -delete
echo "[$(date)] Retention cleanup completed."
