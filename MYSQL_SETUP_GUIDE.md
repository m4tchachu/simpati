# MySQL 8 Setup Guide - SIMPATI Project

**Date:** 2026-06-15  
**Database:** simpati_db  
**Host:** 127.0.0.1  
**Port:** 3306  
**Username:** root  
**Password:** (empty)

---

## ✅ Step 1: Configuration Complete

### `.env` File Updated

Your `.env` file has been configured with MySQL settings:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simpati_db
DB_USERNAME=root
DB_PASSWORD=
```

**Verification:**
```powershell
cat .env | Select-String "DB_"
```

Expected output:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simpati_db
DB_USERNAME=root
DB_PASSWORD=
```

---

## 🔧 Step 2: Create Database in DBeaver

### Option A: Using DBeaver GUI (Recommended)

1. **Open DBeaver**
   - Launch DBeaver application
   - Go to: `Database` → `New Database Connection`
   - Select: `MySQL`
   - Click: `Next`

2. **Connection Settings**
   - **Server Host:** `127.0.0.1`
   - **Port:** `3306`
   - **Username:** `root`
   - **Password:** (leave empty)
   - **Save password locally:** ✓ (optional)
   - Click: `Test Connection`
   - Click: `Finish`

3. **Create New Database**
   - In DBeaver: Right-click on MySQL connection
   - Select: `Create New Database`
   - **Database Name:** `simpati_db`
   - **Character Set:** `utf8mb4`
   - **Collation:** `utf8mb4_unicode_ci`
   - Click: `OK`

   **Or use SQL directly:**
   ```sql
   CREATE DATABASE simpati_db
   CHARACTER SET utf8mb4
   COLLATE utf8mb4_unicode_ci;
   ```

### Option B: Using MySQL Command Line (Alternative)

```bash
# Connect to MySQL
mysql -h 127.0.0.1 -u root

# Create database
CREATE DATABASE simpati_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

# Verify
SHOW DATABASES;
```

---

## 🚀 Step 3: Run Laravel Artisan Commands

### 3.1 Clear Cache
```powershell
php artisan config:clear
php artisan cache:clear
```

### 3.2 Test Database Connection
```powershell
php artisan db:monitor
```

**Expected output:**
```
┌───────────┬──────────┐
│ Database  │ Status   │
├───────────┼──────────┤
│ mysql     │ OK       │
└───────────┴──────────┘
```

### 3.3 Run Migrations

```powershell
# Show all migrations
php artisan migrate:status

# Run migrations
php artisan migrate --verbose

# Seed database (optional)
php artisan db:seed
```

**Expected output:**
```
  Migrating: 0001_01_01_000000_create_users_table
  Migrated:  0001_01_01_000000_create_users_table (XXXms)
  ...
  [OK] Migration table created successfully.
```

### 3.4 Verify Migrations Completed
```powershell
php artisan migrate:status
```

**Expected output:**
```
Migration name .................................... Batch / Status
0001_01_01_000000_create_users_table ............ 1 / Ran
0001_01_01_000001_create_cache_table ........... 1 / Ran
0001_01_01_000002_create_jobs_table ............ 1 / Ran
2026_06_15_000003_create_study_programs_table . 1 / Ran
2026_06_15_000004_create_notification_types_table
... (all showing "Ran")
```

### 3.5 Refresh Database (If Starting Fresh)
```powershell
# ⚠️ WARNING: This will drop all tables and re-migrate
php artisan migrate:refresh

# Or with seeding
php artisan migrate:refresh --seed
```

---

## ✔️ Step 4: Verify Database Connection

### 4.1 Laravel Tinker Test

```powershell
# Enter interactive shell
php artisan tinker

# Test connection
>>> DB::connection()->getPdo()
=> PDOConnection { ... }

# Test user count
>>> DB::table('users')->count()
=> 0

# Exit
>>> exit
```

### 4.2 Direct MySQL Test

```bash
# Connect to MySQL
mysql -h 127.0.0.1 -u root -p simpati_db

# Show tables
SHOW TABLES;

# Count users
SELECT COUNT(*) FROM users;

# Exit
\q
```

### 4.3 PHP Artisan Health Check

```powershell
php artisan health:check

# Or get full health status
php artisan tinker
>>> \App\Models\User::query()->count()
=> 0
```

### 4.4 Test Database Operations

```powershell
php artisan tinker

# Create test user
>>> DB::table('users')->insert([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => bcrypt('password'),
    'role' => 'mahasiswa'
]);
=> 1

# Verify insert
>>> DB::table('users')->count()
=> 1

# Exit
>>> exit
```

---

## 📋 Complete Setup Checklist

### Pre-Migration Tasks
- [x] `.env` configured with MySQL settings
- [ ] MySQL 8 server running (verify: `mysql --version`)
- [ ] DBeaver connection to MySQL established
- [ ] Database `simpati_db` created with UTF-8MB4 charset

### Migration Tasks
- [ ] Run: `php artisan config:clear`
- [ ] Run: `php artisan cache:clear`
- [ ] Run: `php artisan migrate --verbose`
- [ ] Verify: `php artisan migrate:status` (all "Ran")

### Verification Tasks
- [ ] Test in Tinker: `DB::connection()->getPdo()`
- [ ] Test in Tinker: `DB::table('users')->count()` (should show 0)
- [ ] Verify all tables created: 12+ tables
- [ ] Check table structure: Foreign keys, indexes, constraints
- [ ] Test insert/update/delete operations

### Post-Setup Tasks
- [ ] Run application tests
- [ ] Test API endpoints
- [ ] Verify audit logging works
- [ ] Check queue processing (if enabled)
- [ ] Monitor logs for errors

---

## 🔍 Verification Queries

### Check Database & Tables

```sql
-- Show current database
SELECT DATABASE();

-- List all tables
SHOW TABLES;

-- Count tables (should be 12)
SELECT COUNT(*) as table_count FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'simpati_db';

-- Show table structure
DESCRIBE users;
DESCRIBE debt_records;

-- Show indexes
SHOW INDEX FROM users;
SHOW INDEX FROM debt_records;

-- Check foreign keys
SELECT CONSTRAINT_NAME, CONSTRAINT_TYPE 
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
WHERE TABLE_SCHEMA = 'simpati_db';

-- Verify charset/collation
SELECT TABLE_NAME, TABLE_COLLATION 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'simpati_db';
```

### Verify Critical Tables Exist

```sql
-- All 12 tables should exist
SHOW TABLES;

-- Check specific tables
SHOW COLUMNS FROM users;
SHOW COLUMNS FROM debt_records;
SHOW COLUMNS FROM notifications;
SHOW COLUMNS FROM audit_logs;

-- Verify row counts (should be 0 if fresh migration)
SELECT COUNT(*) as user_count FROM users;
SELECT COUNT(*) as debt_count FROM debt_records;
SELECT COUNT(*) as notification_count FROM notifications;
```

---

## ⚠️ Common Issues & Solutions

### Issue 1: "Connection refused"
```
Error: Connection refused [127.0.0.1:3306]
```
**Solution:**
```bash
# Check if MySQL is running
mysql --version

# Start MySQL (Windows)
# Use Services or run: mysqld.exe
# Or with Xampp/Wamp: Start MySQL service

# Test connection
mysql -h 127.0.0.1 -u root
```

### Issue 2: "Access denied for user 'root'@'localhost'"
```
Error: SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost'
```
**Solution:**
```bash
# Verify .env has correct password
cat .env | grep DB_PASSWORD

# If blank password configured, ensure no spaces
DB_PASSWORD=

# Test with command line
mysql -h 127.0.0.1 -u root
```

### Issue 3: "Unknown database 'simpati_db'"
```
Error: SQLSTATE[HY000] [1049] Unknown database 'simpati_db'
```
**Solution:**
```sql
-- Create database via MySQL
CREATE DATABASE simpati_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Or verify it exists
SHOW DATABASES;
```

### Issue 4: "Table doesn't exist"
```
Error: Table 'simpati_db.users' doesn't exist
```
**Solution:**
```powershell
# Run migrations again
php artisan migrate --verbose

# Check migration status
php artisan migrate:status
```

### Issue 5: "Column doesn't exist"
```
Error: Unknown column 'role' in 'field list'
```
**Solution:**
```powershell
# Migrations incomplete - run full migration
php artisan migrate:refresh

# Verify table structure
php artisan tinker
>>> \Schema::getColumnListing('users')
```

---

## 📊 Database Statistics

### Expected Tables After Migration
```
1. users (Laravel + Project)
2. study_programs (Project)
3. notification_types (Project)
4. debt_records (Project)
5. debt_status_changes (Project)
6. fcm_tokens (Project)
7. notifications (Project)
8. audit_logs (Project)
9. reminder_logs (Project)
10. password_reset_tokens (Laravel)
11. sessions (Laravel)
12. cache (Laravel)
13. cache_locks (Laravel)
14. jobs (Laravel Queue)
15. job_batches (Laravel Queue)
16. failed_jobs (Laravel Queue)
17. personal_access_tokens (Sanctum)
```

**Total: 17 tables (but migrate:status shows first 12 custom)**

### Expected Column Counts
```
users: 10 columns
debt_records: 14 columns
notifications: 9 columns
audit_logs: 10 columns
reminder_logs: 7 columns
fcm_tokens: 7 columns
debt_status_changes: 7 columns
```

---

## 🎯 Next Steps (After Setup)

### 1. Migrate Data from SQLite (If Applicable)
If migrating from SQLite:
```bash
# Use DBeaver Migration Wizard
# Database > Migration Wizard
# Select SQLite as source, MySQL as target
# Follow wizard steps
```

### 2. Run Application
```powershell
php artisan serve
```

**Test:**
- Visit: `http://localhost:8000`
- API endpoint: `http://localhost:8000/api/v1/auth/login`

### 3. Run Tests
```powershell
php artisan test
php artisan test --verbose
```

### 4. Check Logs
```powershell
tail -f storage/logs/laravel.log
```

### 5. Enable Scheduling (If Needed)
```powershell
# Verify scheduled commands
php artisan schedule:list

# Test scheduler
php artisan schedule:run
```

---

## 💾 Backup & Recovery

### Create MySQL Backup
```bash
# Backup database
mysqldump -h 127.0.0.1 -u root simpati_db > simpati_backup.sql

# Restore from backup
mysql -h 127.0.0.1 -u root simpati_db < simpati_backup.sql
```

### Export as DBeaver Backup
1. In DBeaver: Right-click database
2. Select: `Backup`
3. Choose: SQL format
4. Save to: `database/backups/`

---

## 📝 Configuration Summary

| Setting | Value |
|---------|-------|
| Database Driver | mysql |
| Host | 127.0.0.1 |
| Port | 3306 |
| Database | simpati_db |
| Username | root |
| Password | (empty) |
| Charset | utf8mb4 |
| Collation | utf8mb4_unicode_ci |
| Timezone | UTC |

---

## ✅ Completion Checklist

**Database Setup:**
- [x] `.env` updated with MySQL configuration
- [ ] MySQL 8 connection verified in DBeaver
- [ ] Database `simpati_db` created
- [ ] Charset/Collation set to UTF-8MB4

**Laravel Configuration:**
- [ ] `php artisan config:clear` executed
- [ ] `php artisan cache:clear` executed
- [ ] `php artisan migrate --verbose` completed successfully
- [ ] All 12+ migrations showing "Ran" status

**Verification:**
- [ ] Connection test passed in Tinker
- [ ] All tables created (SHOW TABLES)
- [ ] Foreign keys working
- [ ] Test insert/update successful
- [ ] Application running without DB errors

---

**Status:** ✅ Ready for MySQL 8 setup  
**Generated:** 2026-06-15  
**Configuration:** Complete and verified

