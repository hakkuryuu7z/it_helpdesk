# Centralized IT Helpdesk System

An enterprise-grade, web-based IT Service Management (ITSM) and Feature Engineering lifecycle platform built for distributed, multi-branch organizations.

[![Laravel Version](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-blue.svg)](https://www.postgresql.org)
[![Automated Tests](https://img.shields.io/badge/Tests-74%20Passed%20(248%20assertions)-brightgreen.svg)]()
[![Security Hardened](https://img.shields.io/badge/Security-CWE--1236%20%7C%20IDOR%20%7C%20CSRF%20Protected-success.svg)]()

---

## 1. System Overview & Core Capabilities

The **Centralized IT Helpdesk System** connects headquarters and regional branches into a single unified operational dashboard while enforcing strict branch-isolated access boundaries:

- **Multi-Branch Isolation with Central Oversight**: Regular users and branch staff are confined to their home branch. Branch EDP personnel can manage multiple designated branches via explicit assignment mapping (`branch_edp_assignments`). Central EDP and Super Administrators monitor and triage incidents across all branches.
- **Three Ticket Modalities**:
  - **Incidents & Complaints**: Technical failures, network outages, printer malfunctions, hardware errors.
  - **Service Requests**: Access requests, ERP account provisioning, workstation installations.
  - **Feature Requests**: Software change proposals with a dedicated 9-stage engineering lifecycle (`submitted` &rarr; `under_review` &rarr; `accepted` &rarr; `planning` &rarr; `development` &rarr; `testing` &rarr; `deployment` &rarr; `completed`).
- **Monotonic Gap-Free Ticket Numbering**: Generates concurrency-safe, audit-compliant ticket IDs (`HD-YYYY-000001`) using PostgreSQL `lockForUpdate()` database sequences.
- **Bi-Directional Discussion & Confidential EDP Notes**: Tickets feature full audit-stamped discussions. Internal notes (`is_internal = true`) are strictly accessible to authorized EDP personnel and are invisible to regular employees.
- **Secure Private Attachments**: Uploaded diagnostic files, logs, and screenshots are stored in private storage (never in `public/`), with UUID obfuscation, MIME verification, executable blocking, and authorized streaming downloads.
- **Role-Aware Realtime Dashboards**: Dynamic operational portals tailored for each persona (Employee Portal, Branch Technician Workbench, Central Operations Center, and Super Admin Control Center).
- **Secure Analytics & CSV Streaming Export**: Fast, streaming CSV exports with formula injection sanitization (CWE-1236 defense) that strictly respect user branch boundaries.

---

## 2. Technology Stack

- **Backend**: PHP 8.2+, Laravel 11.x (Modular Monolith)
- **Database**: PostgreSQL 16 (Relational integrity, foreign keys, JSONB audit values, `EPOCH` metrics)
- **Authorization**: Spatie Laravel Permission 6.x + Laravel Resource Policies + Scoped Eloquent Queries
- **Frontend**: Laravel Blade, Bootstrap 5.3, Bootstrap Icons, Alpine.js, Vite 5.x
- **Containerization**: Docker & Docker Compose (Multi-stage build, Nginx 1.27, PHP-FPM 8.2 Alpine, PostgreSQL 16 Alpine, Queue Worker, Scheduler)
- **Testing**: PHPUnit / Laravel Test Suite (74 feature & unit tests, 248 assertions)

---

## 3. User Roles & Permission Matrix

| Role | Branch Scope | Ticket Access | Feature Requests | Master Data Admin | Audit Trail |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Super Admin** | System-wide | Full access | Full lifecycle | Full CRUD (Users, Branches, Depts, Roles) | Full View |
| **Central EDP** | System-wide | View all, Triage, Assign, Escalate, Resolve | Review, Accept/Reject, Stage Transitions, Architecture | View Branches, Departments, Users | Ticket Audit View |
| **Branch EDP** | Assigned Branches | View & handle tickets in assigned branches | View branch proposals | View branch users | Branch Audit View |
| **Branch Admin** | Own Branch | View branch tickets & reports | View branch proposals | Manage users within own branch | Branch Audit View |
| **Regular User**| Own Tickets Only| Strictly own tickets & public comments | Propose new features | None | Own Ticket History |

---

## 4. Default Demonstration Credentials

The application is pre-seeded with synthetic test accounts across all roles (default password: `password`):

| Role | Email | Password | Branch Context |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `superadmin@helpdesk.local` | `password` | System-wide (HQ) |
| **Central EDP** | `central.edp@helpdesk.local` | `password` | System-wide (HQ) |
| **Branch EDP (Bandung)** | `edp.bdg@helpdesk.local` | `password` | BDG (Bandung) & SMG (Semarang) |
| **Branch EDP (Surabaya)** | `edp.sby@helpdesk.local` | `password` | SBY (Surabaya) |
| **Branch Admin (Bandung)** | `admin.bdg@helpdesk.local` | `password` | BDG (Bandung) |
| **Regular User (Bandung)** | `user.bdg@helpdesk.local` | `password` | BDG (Bandung) |
| **Regular User (Surabaya)** | `user.sby@helpdesk.local` | `password` | SBY (Surabaya) |
| **Regular User (HQ)** | `user.hq@helpdesk.local` | `password` | HQ (Head Office) |

---

## 5. Local Development Setup

### Prerequisites
- PHP 8.2 or higher
- Composer 2.x
- Node.js 18+ and NPM
- PostgreSQL 16+

### Quickstart Commands
```bash
# 1. Clone repository
git clone https://github.com/your-org/EDP_Management.git
cd EDP_Management

# 2. Install PHP and JS dependencies
composer install
npm install

# 3. Configure environment
cp .env.example .env
php artisan key:generate

# Configure database credentials in .env:
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=helpdesk_db
# DB_USERNAME=postgres
# DB_PASSWORD=your_password

# 4. Run migrations and seed synthetic demonstration data
php artisan migrate --seed

# 5. Build frontend assets
npm run build

# 6. Run automated test suite
php artisan test

# 7. Start local server
php artisan serve
```

---

## 6. Production VPS Deployment (Docker Compose)

For production deployment on an Ubuntu Linux VPS, see the comprehensive [Deployment & Operations Handbook](DEPLOYMENT_AND_OPERATIONS_HANDBOOK.md).

Quick production startup:
```bash
# Verify environment configuration
cp .env.example .env

# Build and launch production containers
docker compose -f compose.prod.yaml up -d --build

# Run migrations and seed initial roles
docker compose -f compose.prod.yaml exec app php artisan migrate --force --seed
```

---

## 7. Automated Backups & Disaster Recovery

- **Automated Backup**: `bash scripts/backup.sh` executes a compressed database dump with 14-day retention.
- **Automated Restore**: `bash scripts/restore.sh /path/to/backup_file.sql.gz` terminates active sessions and safely restores schema and data.
