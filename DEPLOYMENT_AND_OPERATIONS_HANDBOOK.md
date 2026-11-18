# Centralized IT Helpdesk System: Deployment & Operations Handbook

This document provides a production runbook for deploying, securing, and maintaining the Centralized IT Helpdesk System on an Ubuntu Linux VPS (Ubuntu 22.04 LTS / 24.04 LTS) using Docker Compose, Nginx, PHP-FPM 8.2, and PostgreSQL 16.

---

## 1. Architecture Summary

The production topology uses containerized micro-services within a private Docker bridge network:

```
[ Internet ]
     │
     ▼
[ Host Firewall (UFW) ] (Ports 80, 443, 22)
     │
     ▼
[ Nginx Reverse Proxy Container ] (Port 80/443 SSL Termination)
     │
     ├── /build/*, /images/* ──► [ Public Static Assets Volume ]
     └── *.php, /api/*       ──► [ PHP-FPM App Container (helpdesk_app_prod:9000) ]
                                      │
                                      ├──► [ PostgreSQL 16 Container (helpdesk_postgres_prod:5432) ]
                                      ├──► [ Queue Worker Container (helpdesk_queue_prod) ]
                                      └──► [ Scheduler Container (helpdesk_scheduler_prod) ]
```

---

## 2. Server Provisioning & Prerequisites

### 2.1 Hardware Requirements
- **CPU**: 2 vCPU minimum (4 vCPU recommended for organizations > 500 users)
- **RAM**: 4 GB minimum (8 GB recommended for database caching and queue processing)
- **Disk**: 40 GB SSD (NVMe preferred)

### 2.2 Host Package Installation
Execute on the fresh Ubuntu server:

```bash
# Update repositories
sudo apt update && sudo apt upgrade -y

# Install essential utilities
sudo apt install -y curl git ufw fail2ban certbot python3-certbot-nginx htop unzip gzip

# Install Docker Engine and Compose Plugin
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER
```

### 2.3 Host Firewall Configuration
```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP (Let's Encrypt validation)
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable
```

---

## 3. Production Deployment Step-by-Step

### Step 1: Clone Repository
```bash
sudo mkdir -p /var/www/helpdesk
sudo chown $USER:$USER /var/www/helpdesk
cd /var/www/helpdesk
git clone https://github.com/your-org/EDP_Management.git .
```

### Step 2: Configure Environment Variables
Create the production `.env` file:
```bash
cp .env.example .env
nano .env
```

Ensure critical production values are set:
```ini
APP_NAME="Centralized IT Helpdesk"
APP_ENV=production
APP_KEY=base64:... # Generate using `docker compose -f compose.prod.yaml run --rm app php artisan key:generate`
APP_DEBUG=false
APP_URL=https://helpdesk.yourcompany.com

LOG_CHANNEL=daily
LOG_LEVEL=info

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=helpdesk_prod_db
DB_USERNAME=helpdesk_prod_user
DB_PASSWORD=YOUR_HIGH_ENTROPY_PASSWORD_HERE

QUEUE_CONNECTION=database
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true

FILESYSTEM_DISK=local
```

### Step 3: Obtain SSL Certificate
Obtain a valid Let's Encrypt certificate for your domain:
```bash
sudo certbot certonly --standalone -d helpdesk.yourcompany.com
```
Certificates will be stored at `/etc/letsencrypt/live/helpdesk.yourcompany.com/`, which is mounted into the Nginx container as defined in `compose.prod.yaml`.

### Step 4: Build & Start Production Containers
```bash
# Build multi-stage image (frontend assets + optimized PHP runtime) and start daemonized services
docker compose -f compose.prod.yaml up -d --build
```

### Step 5: Execute Database Migrations & Initial Setup
```bash
# Run database migrations with force flag
docker compose -f compose.prod.yaml exec app php artisan migrate --force

# Seed roles, permissions, master branches, and initial administrative account
docker compose -f compose.prod.yaml exec app php artisan db:seed --force

# Optimize Laravel route, configuration, and view caching
docker compose -f compose.prod.yaml exec app php artisan config:cache
docker compose -f compose.prod.yaml exec app php artisan route:cache
docker compose -f compose.prod.yaml exec app php artisan view:cache
```

---

## 4. Automated Backup Schedule & Disaster Recovery

### 4.1 Daily Automated Backup via Crontab
Configure a daily cron job at 02:00 AM on the host:
```bash
sudo crontab -e
```
Add the following line:
```cron
0 2 * * * /bin/bash /var/www/helpdesk/scripts/backup.sh >> /var/log/helpdesk_backup.log 2>&1
```

### 4.2 Disaster Recovery Procedure
To restore from a backup:
```bash
# Execute restore script with the backup path
bash /var/www/helpdesk/scripts/restore.sh /var/backups/helpdesk/helpdesk_db_20261118_020000.sql.gz
```

---

## 5. Operations & Maintenance Runbook

### Inspecting Service Logs
```bash
# App and PHP-FPM logs
docker compose -f compose.prod.yaml logs -f app

# Nginx web server access & error logs
docker compose -f compose.prod.yaml logs -f nginx

# Background queue worker
docker compose -f compose.prod.yaml logs -f queue

# Database logs
docker compose -f compose.prod.yaml logs -f postgres
```

### Restarting Services
```bash
# Graceful restart of queue workers after code changes
docker compose -f compose.prod.yaml exec app php artisan queue:restart

# Restart entire stack
docker compose -f compose.prod.yaml restart
```

### Clearing Application Cache
```bash
docker compose -f compose.prod.yaml exec app php artisan optimize:clear
docker compose -f compose.prod.yaml exec app php artisan optimize
```

---

## 6. Troubleshooting Guide

| Issue | Potential Cause | Resolution |
| :--- | :--- | :--- |
| **502 Bad Gateway** | `helpdesk_app_prod` container is down or starting up | Check `docker compose -f compose.prod.yaml ps`. Verify PHP-FPM socket on port 9000. |
| **Database Connection Refused** | PostgreSQL container healthy check failed or password mismatch | Verify `DB_HOST=postgres`, check `docker compose -f compose.prod.yaml logs postgres`. |
| **File Upload Error / 403 Forbidden** | Storage folder ownership permissions | Run: `docker compose -f compose.prod.yaml exec app chown -R www-data:www-data /var/www/html/storage`. |
| **Email or Notifications Delay** | Queue worker process crashed | Check `docker compose -f compose.prod.yaml logs queue` and restart with `docker compose -f compose.prod.yaml restart queue`. |
| **Expired SSL Certificate** | Certbot auto-renewal failed | Run `sudo certbot renew && docker compose -f compose.prod.yaml restart nginx`. |
