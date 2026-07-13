#!/usr/bin/env bash
#
# Sauvegarde quotidienne de la base MySQL de Cyna, avec compression et
# rétention glissante. Prévu pour un déploiement Docker Compose.
#
# Usage : ./backup-db.sh  (à planifier via cron — voir INFRA_SAUVEGARDE_SSL.md)
#
set -euo pipefail

# --- Configuration (à adapter / surcharger par variables d'environnement) ---
DB_CONTAINER="${DB_CONTAINER:-cyna-db}"
DB_NAME="${DB_NAME:-cyna}"
DB_USER="${DB_USER:-cyna}"
DB_PASSWORD="${DB_PASSWORD:?La variable DB_PASSWORD est requise}"
BACKUP_DIR="${BACKUP_DIR:-/opt/cyna/backups}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"

# --- Sauvegarde -------------------------------------------------------------
mkdir -p "$BACKUP_DIR"
STAMP="$(date +%F)"                    # ex : 2026-07-01
OUTFILE="$BACKUP_DIR/cyna-$STAMP.sql.gz"

echo "[$(date '+%F %T')] Démarrage de la sauvegarde de « $DB_NAME »…"

docker exec "$DB_CONTAINER" \
  mysqldump --single-transaction --quick --routines --triggers \
    -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" \
  | gzip -9 > "$OUTFILE"

echo "[$(date '+%F %T')] Sauvegarde écrite : $OUTFILE ($(du -h "$OUTFILE" | cut -f1))"

# --- Rétention : suppression des archives trop anciennes --------------------
find "$BACKUP_DIR" -name 'cyna-*.sql.gz' -type f -mtime "+$RETENTION_DAYS" -print -delete

echo "[$(date '+%F %T')] Sauvegarde terminée. Rétention : $RETENTION_DAYS jours."
