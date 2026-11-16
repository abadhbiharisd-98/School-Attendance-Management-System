# 🏫 School Attendance Management System
### Modernized for PHP 8.2 &bull; Render Docker Deployment &bull; Aiven Cloud MySQL Ready

A robust, web-based School Attendance Management System upgraded for **PHP 8.2**, containerized for **Render Cloud**, and configured for **Aiven Cloud MySQL** with SSL encryption and reverse proxy port handling.

---

## 🚀 Live App Endpoints

| Resource | URL Path | Description |
|---|---|---|
| **Home Page** | `/` or `/index.php` | Main student and course attendance portal |
| **1-Click DB Setup** | `/db_setup.php` | Visual Aiven SSL health check & database importer |
| **Sign In** | `/index.php?signIn=1` | User and Lecturer login interface |
| **Admin Control Panel** | `/admin/pageHome.php` | Administrator dashboard & system settings |
| **Admin Direct Router** | `/admin/login.php` | Direct routing to prevent slash redirect issues |

---

## 🔑 Default Login Credentials

| Role | Username | Password | Permissions |
|---|---|---|---|
| **Super Admin** | `admin` | `admin` | Full system control, user management, backups |
| **Lecturer / User** | `henry` | `123456789Henry` | Mark attendance, manage assigned courses |
| **Guest** | *Anonymous* | *None* | View public course lists and schedules |

---

## ⚙️ Aiven Cloud MySQL Configuration

| Variable | Configured Value |
|---|---|
| `DB_SERVER` | `mysql-32d19a6e-abadhbihari-33eb.f.aivencloud.com` |
| `DB_PORT` | `12893` |
| `DB_USERNAME` | `avnadmin` |
| `DB_PASSWORD` | *(Configured via Render Environment Variables / Auto-configured)* |
| `DB_NAME` | `school_attendance` |
| `DB_SSL` | `true` *(Enforced by Aiven)* |
| `BASE_URL` | `https://school-attendance-management-system-483x.onrender.com` |

---

## 🛠️ Key Architectural Upgrades & Fixes

### 1. Aiven Cloud MySQL Compatibility
* **SSL Encryption (`MYSQLI_CLIENT_SSL`)**: Aiven enforces `require_secure_transport=ON`. Upgraded `db.php` initializes `mysqli_init()` with SSL client flags to prevent connection rejection.
* **5-Digit Custom Port Support**: Safely casts and routes custom ports (e.g. `26284`) in both `db.php` and `admin/incFunctions.php`.
* **Zero-Downtime Fallback**: Automatically connects to local/plain MySQL if SSL is disabled (`DB_SSL=false`).

### 2. PHP 8.x Compatibility
* **Strict `mysqli_result` Type Checking**: Added `if ($qry && $qry instanceof mysqli_result)` across all `db_fetch_*` functions in `db.php` to prevent PHP 8.2 `TypeError` fatal exceptions on empty/uninitialized databases.
* **Removed Legacy Functions**: Replaced deprecated `each()` in `ci_input.php` with PHP 8-compliant `foreach()`.
* **PHP 8 Polyfills**: Added transparent polyfills for `get_magic_quotes_gpc()`, `get_magic_quotes_runtime()`, and `set_magic_quotes_runtime()`.
* **Safe Property Initialization**: Fixed dynamic properties and constructors to follow PHP 8 standards.

### 3. Render Reverse Proxy & Port 10000 Fixes
* **Dynamic Port Binding**: `start.sh` dynamically binds Apache's `ports.conf` and `000-default.conf` to `${PORT}` assigned by Render.
* **Port 10000 Leak Prevention**: Configured Apache `ServerName localhost`, `UseCanonicalName Off`, and `UseCanonicalPhysicalPort Off` to prevent internal container port (`:10000`) from leaking into public HTTP 301 redirects.
* **Smart Trailing Slash `.htaccess`**: Port-stripping rewrite rules detect `X-Forwarded-Proto: https` and clean any port numbers from public redirects.
* **Direct File Routing**: Added `admin/login.php` and direct file targets to eliminate directory slash redirects.

---

## 📦 How to Deploy on Render

### Step 1: Push Code to GitHub
```bash
git init
git add .
git commit -m "Deploy: School Attendance Management System for Render & Aiven MySQL"
git branch -M main
git remote add origin https://github.com/<YOUR-USERNAME>/<YOUR-REPO-NAME>.git
git push -u origin main
```

### Step 2: Create Web Service on Render
1. Go to [Render Dashboard](https://dashboard.render.com/).
2. Click **New +** &rarr; **Web Service**.
3. Connect your GitHub repository.
4. Select **Docker** as the Environment / Runtime.
5. Under **Environment Variables**, add:
   * `DB_SERVER`: `<your-aiven-mysql-host>`
   * `DB_PORT`: `<your-aiven-port>` (e.g. `26284`)
   * `DB_USERNAME`: `avnadmin`
   * `DB_PASSWORD`: `<your-aiven-password>`
   * `DB_NAME`: `defaultdb`
   * `DB_SSL`: `true`
   * `BASE_URL`: `https://<your-service-name>.onrender.com`
6. Click **Create Web Service**.

### Step 3: Run 1-Click Database Setup
Once Render finishes deploying:
1. Open your browser and navigate to:
   ```
   https://<your-service-name>.onrender.com/db_setup.php
   ```
2. Verify the **SSL Active** status badge.
3. Click the blue **"🚀 Import Database Now"** button.
4. All 9 tables and default users will be imported in seconds.
5. Click **"🚀 Login Now"** and sign in as `admin` / `admin`!

---

## 📂 Project Structure
```text
├── Dockerfile                  # Production PHP 8.2 Apache container definition
├── start.sh                    # Dynamic port & reverse proxy startup script
├── render.yaml                 # Infrastructure as Code (IaC) Render Blueprint
├── .htaccess                   # Port-stripping & reverse-proxy trailing slash rules
├── index.php                   # Root router
├── db_setup.php                # Visual Aiven SSL health check & 1-click installer
├── README.md                   # Complete deployment & configuration documentation
├── School_Attendance_Management_System/
│   ├── attendance/
│   │   ├── config.php          # Dynamic config supporting Render environment variables
│   │   ├── db.php              # Modernized SSL & PHP 8.x database driver
│   │   ├── index.php           # Landing and application entry point
│   │   ├── Database/
│   │   │   └── attendance_management.sql # Database dump
│   │   ├── admin/
│   │   │   ├── incFunctions.php # Core functions with SSL & PHP 8.2 null checks
│   │   │   ├── index.php       # Port-safe admin redirect router
│   │   │   ├── login.php       # Direct admin login file router
│   │   │   └── pageHome.php    # Admin control panel dashboard
│   │   └── ...
```

---

## 🔒 Security Notes
* Ensure `DB_SSL` remains `true` to keep database communication strictly encrypted over TLS.
* After running `db_setup.php` on production, you can disable or restrict access to `/db_setup.php` for extra security.
* All original files were safely backed up with `.original` extension prior to modernization.
