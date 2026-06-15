# MySQL Setup Quick Reference

## ✅ Step 1: .env Configuration

**Status:** ✅ **COMPLETE**

Your `.env` has been updated with:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simpati_db
DB_USERNAME=root
DB_PASSWORD=
```

---

## 🔧 Step 2: Create Database in DBeaver

### Quick Steps:

1. **Open DBeaver** → Right-click MySQL connection
2. **Create New Database**
   - Name: `simpati_db`
   - Character Set: `utf8mb4`
   - Collation: `utf8mb4_unicode_ci`
   - Click: **OK**

**Or execute SQL:**
```sql
CREATE DATABASE simpati_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

---

## 🚀 Step 3: Run Artisan Commands (in order)

```powershell
# 1. Clear caches
php artisan config:clear
php artisan cache:clear

# 2. Test connection (optional)
php artisan db:monitor

# 3. Run migrations
php artisan migrate --verbose

# 4. Verify migrations
php artisan migrate:status
```

**Expected last output:** All migrations showing `Ran` status

---

## ✔️ Step 4: Verify Connection

### Quick Test (Choose One):

**Option A - Tinker (Fastest):**
```powershell
php artisan tinker
>>> DB::connection()->getPdo()
>>> DB::table('users')->count()
>>> exit
```

**Option B - MySQL CLI:**
```bash
mysql -h 127.0.0.1 -u root -p simpati_db

# Then:
SHOW TABLES;
SELECT COUNT(*) FROM users;
\q
```

**Option C - Health Check:**
```powershell
php artisan health:check
```

---

## 📋 Expected Results

### After `php artisan migrate --verbose`:
- ✅ 12 migrations executed successfully
- ✅ All migrations show `Ran` status
- ✅ 12+ tables created in `simpati_db`

### After `php artisan db:monitor`:
```
┌───────────┬──────────┐
│ Database  │ Status   │
├───────────┼──────────┤
│ mysql     │ OK       │
└───────────┴──────────┘
```

### Database Tables Created:
```
users
study_programs
notification_types
debt_records
debt_status_changes
fcm_tokens
notifications
audit_logs
reminder_logs
personal_access_tokens
cache
sessions
jobs
job_batches
failed_jobs
```

---

## ⚠️ If Something Fails

### Connection Failed?
```powershell
# Check MySQL is running
mysql --version

# Check .env
cat .env | Select-String "DB_"

# Test direct connection
mysql -h 127.0.0.1 -u root
```

### Migrations Failed?
```powershell
# Check migration status
php artisan migrate:status

# Rollback and retry
php artisan migrate:rollback
php artisan migrate --verbose
```

### Database Not Found?
```sql
-- Create via MySQL CLI
CREATE DATABASE simpati_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Verify
SHOW DATABASES;
```

---

## 📊 Configuration Summary

| Parameter | Value |
|-----------|-------|
| **Database** | simpati_db |
| **Host** | 127.0.0.1 |
| **Port** | 3306 |
| **Username** | root |
| **Password** | (empty) |
| **Charset** | utf8mb4 |
| **Collation** | utf8mb4_unicode_ci |

---

## ✅ Checklist

- [x] `.env` updated
- [ ] Database created in DBeaver
- [ ] `php artisan config:clear` executed
- [ ] `php artisan cache:clear` executed
- [ ] `php artisan migrate --verbose` completed
- [ ] `php artisan migrate:status` all showing "Ran"
- [ ] Connection verified

---

**See `MYSQL_SETUP_GUIDE.md` for detailed instructions**

