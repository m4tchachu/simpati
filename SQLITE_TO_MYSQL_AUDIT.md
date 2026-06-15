# SQLite to MySQL Migration - Comprehensive Audit Report

**Date:** 2026-06-15  
**Project:** SIMPATI (Debt Tracking System)  
**Current Database:** SQLite  
**Target Database:** MySQL  
**Status:** ⚠️ Pre-migration audit (NO CHANGES APPLIED YET)

---

## Executive Summary

The SIMPATI project has been audited for SQLite→MySQL migration compatibility. **6 HIGH-RISK issues** identified that require attention before migration.

### Risk Level Summary
- 🔴 **CRITICAL:** 1 issue
- 🟠 **HIGH:** 5 issues
- 🟡 **MEDIUM:** 3 issues
- 🟢 **LOW:** 4 issues

---

## Issues Found

### 🔴 CRITICAL ISSUE #1: `enum()` Type Casting Issues

**Severity:** CRITICAL  
**Impact:** Data corruption possible during migration  
**Files Affected:**
- `database/migrations/0001_01_01_000000_create_users_table.php` (line 18)
- `database/migrations/2026_06_15_000005_create_debt_records_table.php` (lines 17, 24)
- `database/migrations/2026_06_15_000006_create_debt_status_changes_table.php` (lines 13-14)

**Problem:**
```php
// SQLite doesn't enforce enum types at database level
$table->enum('role', ['admin', 'mahasiswa'])->default('mahasiswa');

// SQLite stores as VARCHAR - works fine
// MySQL stores as ENUM - STRICT TYPE CHECKING at DB level
$table->enum('type', ['debt', 'receivable']);
$table->enum('status', ['pending', 'active', 'rejected', 'settled']);
$table->enum('old_status', ['pending', 'active', 'rejected', 'settled']);
$table->enum('new_status', ['pending', 'active', 'rejected', 'settled']);
```

**SQLite Behavior:**
- Stores enum values as VARCHAR strings
- NO validation at DB level
- Accepts ANY string value without error
- Example: Can insert `role = 'superuser'` (invalid)

**MySQL Behavior:**
- Strict ENUM type
- Only accepts predefined values
- **WILL REJECT invalid values during migration**
- Insert with invalid value → Error (column='0')

**Affected Tables & Columns:**
1. `users.role` - Values: 'admin', 'mahasiswa'
2. `debt_records.type` - Values: 'debt', 'receivable'
3. `debt_records.status` - Values: 'pending', 'active', 'rejected', 'settled'
4. `debt_status_changes.old_status` - Same as debt_records.status
5. `debt_status_changes.new_status` - Same as debt_records.status

**Migration Failure Scenario:**
```
ERROR: Data truncated for column 'role' at row X
ERROR: Incorrect value 'invalid_role' for column 'type' in table 'debt_records'
```

**Action Required Before Migration:**
1. ✓ Validate ALL existing data in enum columns
2. ✓ Check for NULL or invalid values
3. ✓ Create data cleanup script if needed

---

### 🟠 HIGH ISSUE #1: Foreign Key Constraint Handling

**Severity:** HIGH  
**Impact:** Migration may fail or skip constraints  
**Files Affected:**
- `database/migrations/2026_06_15_000005_create_debt_records_table.php`
- `database/migrations/0001_01_01_000000_create_users_table.php`
- `database/migrations/2026_06_15_000008_create_notifications_table.php`
- `database/migrations/2026_06_15_000010_create_reminder_logs_table.php`

**Problem:**
```php
// SQLite foreign key constraints are OPTIONAL
$table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
$table->foreignId('counterpart_id')->constrained('users')->onDelete('cascade');

// SQLite: May ignore FK constraints if not explicitly enabled
// MySQL: ENFORCES all FK constraints by default
```

**SQLite Behavior:**
```sql
-- Need to enable explicitly:
PRAGMA foreign_keys = ON;
-- If disabled, orphaned records can exist
```

**MySQL Behavior:**
```sql
-- FK constraints are ALWAYS enforced
-- Cannot insert/update violating referential integrity
-- Migration will FAIL if orphaned records exist
```

**Potential Issues:**
1. Orphaned records in dependent tables
2. NULL references in non-nullable FK columns
3. References to deleted parent records

**Affected Foreign Keys:**
```
users (PK) ← debt_records.creator_id (FK) [CASCADE DELETE]
users (PK) ← debt_records.counterpart_id (FK) [CASCADE DELETE]
study_programs (PK) ← users.study_program_id (FK) [SET NULL]
users (PK) ← debt_status_changes.changed_by_user_id (FK) [CASCADE DELETE]
users (PK) ← notifications.user_id (FK) [CASCADE DELETE]
notification_types (PK) ← notifications.notification_type_id (FK) [CASCADE DELETE]
debt_records (PK) ← notifications.debt_record_id (FK) [SET NULL]
debt_records (PK) ← debt_status_changes.debt_record_id (FK) [CASCADE DELETE]
debt_records (PK) ← reminder_logs.debt_record_id (FK) [CASCADE DELETE]
users (PK) ← reminder_logs.user_id (FK) [CASCADE DELETE]
users (PK) ← audit_logs.user_id (FK) [CASCADE DELETE]
users (PK) ← fcm_tokens.user_id (FK) [CASCADE DELETE]
```

**Action Required Before Migration:**
1. ✓ Run FK constraint validation query
2. ✓ Identify orphaned records
3. ✓ Clean up invalid references
4. ✓ Verify no NULL in non-nullable FK columns

---

### 🟠 HIGH ISSUE #2: Timestamp and DateTime Precision

**Severity:** HIGH  
**Impact:** Data loss or precision issues  
**Files Affected:**
- All migration files with timestamps
- `database/migrations/2026_06_15_000005_create_debt_records_table.php` (line 10-12)
- `database/migrations/0001_01_01_000000_create_users_table.php` (line 21)

**Problem:**
```php
$table->dateTime('transaction_date');
$table->dateTime('due_date');
$table->timestamp('confirmed_at')->nullable();
```

**SQLite Behavior:**
- `datetime()` - Stores as TEXT (YYYY-MM-DD HH:MM:SS.SSS)
- `timestamp()` - Also TEXT format
- Millisecond precision possible
- No automatic timezone handling

**MySQL Behavior:**
- `datetime` - Precision up to 6 decimal places (microseconds)
- `timestamp` - UTC conversion by default
- **Different precision levels can cause rounding**

**Issue Example:**
```
SQLite:  2026-06-15 10:30:45.123456 (microseconds)
MySQL:   2026-06-15 10:30:45.123456 (microseconds) - OK
But: 2026-06-15 10:30:45.1234567 → truncated to 45.123457 (PRECISION LOSS)
```

**Affected Columns:**
- `users.created_at`, `users.updated_at`
- `study_programs.created_at`, `study_programs.updated_at`
- `debt_records.created_at`, `debt_records.updated_at`, `debt_records.transaction_date`, `debt_records.due_date`, `debt_records.confirmed_at`, `debt_records.rejected_at`, `debt_records.settled_at`
- `debt_status_changes.created_at`, `debt_status_changes.updated_at`
- `notifications.created_at`, `notifications.updated_at`, `notifications.read_at`
- `fcm_tokens.created_at`, `fcm_tokens.updated_at`
- `audit_logs.created_at`, `audit_logs.updated_at`
- `reminder_logs.sent_at`, `reminder_logs.created_at`, `reminder_logs.updated_at`
- `notification_types.created_at`, `notification_types.updated_at`
- `personal_access_tokens.created_at`, `personal_access_tokens.updated_at`, `personal_access_tokens.last_used_at`, `personal_access_tokens.expires_at`

**Action Required Before Migration:**
1. ✓ Check for timestamps with microsecond precision
2. ✓ Verify timezone handling is consistent
3. ✓ Monitor timestamp values after migration for correctness

---

### 🟠 HIGH ISSUE #3: Unique Constraint Case Sensitivity

**Severity:** HIGH  
**Impact:** Duplicate entries may be allowed/rejected inconsistently  
**Files Affected:**
- `database/migrations/0001_01_01_000000_create_users_table.php` (line 17, 19)
- `database/migrations/2026_06_15_000003_create_study_programs_table.php` (line 14)
- `database/migrations/2026_06_15_000004_create_notification_types_table.php` (line 13)
- `database/migrations/2026_06_15_000007_create_fcm_tokens_table.php` (line 15)
- `database/migrations/2026_06_15_000010_create_reminder_logs_table.php` (line 17)

**Problem:**
```php
$table->string('email')->unique();           // Case-insensitive in SQLite
$table->string('nim')->nullable()->unique(); // Case-sensitive in MySQL
$table->string('code')->unique();            // Different behavior
$table->unique(['debt_record_id', 'user_id', 'days_before']);
```

**SQLite Behavior:**
- Email: `test@example.com` ≠ `TEST@example.com` (but handled as duplicates)
- Actually case-SENSITIVE for most strings
- Collation: ASCII-only

**MySQL Behavior (UTF8MB4):**
- Default collation: `utf8mb4_unicode_ci` (CASE-INSENSITIVE)
- Email: `test@example.com` = `TEST@example.com` (DUPLICATE!)
- Can cause issues if duplicate-cased emails exist

**Example Issue:**
```
SQLite allows:
  INSERT INTO users (email) VALUES ('test@example.com');
  INSERT INTO users (email) VALUES ('TEST@example.com');
  → 2 rows created (case-sensitive)

MySQL with utf8mb4_unicode_ci:
  INSERT INTO users (email) VALUES ('test@example.com');
  INSERT INTO users (email) VALUES ('TEST@example.com');
  → ERROR: Duplicate entry 'TEST@example.com' (case-insensitive)
```

**Affected Unique Columns:**
- `users.email`
- `users.nim`
- `study_programs.code`
- `notification_types.code`
- `fcm_tokens.token`
- `reminder_logs.unique(['debt_record_id', 'user_id', 'days_before'])`
- `personal_access_tokens.token`
- `password_reset_tokens.email` (PRIMARY KEY)

**Action Required Before Migration:**
1. ✓ Find duplicate emails with different cases
2. ✓ Standardize email format (lowercase)
3. ✓ Check for duplicate NIMs with case variation
4. ✓ Verify FCM token uniqueness

---

### 🟠 HIGH ISSUE #4: Integer vs BIGINT Column Width

**Severity:** HIGH  
**Impact:** Potential overflow for large numbers  
**Files Affected:**
- `database/migrations/0001_01_01_000002_create_jobs_table.php` (lines 12-14)
- `database/migrations/2026_06_15_000009_create_audit_logs_table.php` (line 11)

**Problem:**
```php
// jobs table
$table->unsignedSmallInteger('attempts');      // 0-65,535
$table->unsignedInteger('reserved_at');        // 0-4,294,967,295
$table->unsignedInteger('available_at');       // 0-4,294,967,295
$table->unsignedInteger('created_at');         // Epoch time - WILL OVERFLOW Jan 19, 2038

// audit_logs table
$table->unsignedBigInteger('record_id');       // Safe: 0 to huge number
```

**Critical Issue:**
```
Year 2038 Problem for jobs table!
Current timestamp (2026): ~1,750,000,000 seconds
Max unsignedInteger: 4,294,967,295 seconds
Overflow date: January 19, 2038 03:14:07 UTC

After 2038, jobs.created_at and timestamps will overflow!
```

**Affected Columns:**
- `jobs.reserved_at` (unsignedInteger)
- `jobs.available_at` (unsignedInteger)
- `jobs.created_at` (unsignedInteger)
- `job_batches.cancelled_at` (integer)
- `job_batches.created_at` (integer)
- `job_batches.finished_at` (integer)

**Action Required Before Migration:**
1. ✓ Change to BIGINT for future-proofing
2. ✓ Verify no existing data exceeds limits
3. ⚠️ Consider: Will system run past 2038?

---

### 🟠 HIGH ISSUE #5: JSON Column Handling

**Severity:** HIGH  
**Impact:** Data type conversions, query performance  
**Files Affected:**
- `database/migrations/2026_06_15_000008_create_notifications_table.php` (line 12)
- `database/migrations/2026_06_15_000009_create_audit_logs_table.php` (lines 10-11)
- `database/migrations/0001_01_01_000002_create_jobs_table.php` (line 8, 15)
- `database/migrations/0001_01_01_000002_create_jobs_table.php` (line 20)

**Problem:**
```php
$table->json('data')->nullable();                    // notifications
$table->json('old_values')->nullable();              // audit_logs
$table->json('new_values')->nullable();              // audit_logs
$table->longText('payload');                         // jobs - stored as JSON string
$table->longText('failed_job_ids');                  // job_batches
$table->mediumText('options')->nullable();           // job_batches
```

**SQLite Behavior:**
- JSON stored as TEXT
- No JSON validation or optimization
- Basic string operations only
- NULL stored as 'null' string

**MySQL Behavior:**
- JSON stored in optimized binary format
- JSON_EXTRACT, JSON_SET, etc. available
- Validates JSON structure
- NULL vs empty JSON different

**Issue Example:**
```
SQLite: NULL in JSON column = NULL (TEXT)
MySQL:  NULL in JSON column = NULL or empty JSON object {}

Retrieval inconsistency:
$model->data  // SQLite might return null string 'null'
$model->data  // MySQL returns actual null or decoded array
```

**Affected Columns:**
- `notifications.data` (optional JSON)
- `audit_logs.old_values` (optional JSON)
- `audit_logs.new_values` (optional JSON)
- `jobs.payload` (required JSON)
- `job_batches.failed_job_ids` (required JSON)
- `job_batches.options` (optional JSON)

**Action Required Before Migration:**
1. ✓ Verify JSON format in all columns
2. ✓ Test JSON null handling
3. ✓ Update Model casts if needed
4. ✓ Check JSON serialization in code

---

### 🟡 MEDIUM ISSUE #1: Boolean Column Type

**Severity:** MEDIUM  
**Impact:** Type coercion differences  
**Files Affected:**
- `database/migrations/2026_06_15_000007_create_fcm_tokens_table.php` (line 14)

**Problem:**
```php
$table->boolean('is_active')->default(true);
```

**SQLite Behavior:**
- Boolean stored as INTEGER (0 or 1)
- Accepts 0, 1, true, false, 'true', 'false'
- Loose comparison works

**MySQL Behavior:**
- Boolean stored as TINYINT(1) (0 or 1)
- Strict about type
- Comparison may behave differently

**Affected Columns:**
- `fcm_tokens.is_active`

**Potential Issue:**
```php
// In code
if ($token->is_active) { }  // SQLite: works with 0/1 or true/false
                            // MySQL: works with 0/1 or bool

// Model casting
protected $casts = ['is_active' => 'boolean'];  // Ensure this is present
```

**Action Required Before Migration:**
1. ✓ Verify Model has proper boolean casting
2. ✓ Check all boolean values are 0 or 1 (not NULL)
3. ✓ Test with actual MySQL strict mode

---

### 🟡 MEDIUM ISSUE #2: String Length Validation

**Severity:** MEDIUM  
**Impact:** Data truncation possible  
**Files Affected:**
- Multiple files with string columns

**Problem:**
```php
$table->string('name');              // varchar(255) - SQLite: unlimited
$table->string('email');             // varchar(255)
$table->string('password');          // varchar(255)
$table->string('token', 64);         // varchar(64) - personal_access_tokens
$table->string('ip_address', 45);    // varchar(45) - sessions
$table->uuid('id')->primary();       // varchar(36)
```

**SQLite Behavior:**
- String length ADVISORY (not enforced)
- Can store > 255 chars in varchar(255)
- No automatic truncation

**MySQL Behavior:**
- String length ENFORCED
- Inserts > varchar(N) are TRUNCATED or ERROR
- With strict mode: ERROR
- With non-strict: SILENTLY TRUNCATED

**Affected String Columns:**
- All `varchar(X)` columns without explicit length validation

**Example:**
```
SQLite: INSERT INTO users (email) VALUES ('a' * 500) → SUCCESS
MySQL strict: → ERROR: Data too long for column 'email'
MySQL non-strict: → INSERTED & TRUNCATED to 255 chars
```

**Action Required Before Migration:**
1. ✓ Check max length of existing data in string columns
2. ✓ Verify no data exceeds column varchar(N) limits
3. ✓ Ensure Model validation matches DB constraints

---

### 🟡 MEDIUM ISSUE #3: Index Prefix Limits

**Severity:** MEDIUM  
**Impact:** Index creation may fail on large UTF-8 keys  
**Files Affected:**
- All migration files with indexes on string columns

**Problem:**
```php
$table->index('email');
$table->index('code');
$table->string('fcm_tokens.token')->unique();  // 64 chars
```

**SQLite Behavior:**
- No prefix length limits
- Full column value used in index

**MySQL Behavior:**
- With utf8mb4 charset: Max index prefix = 767 bytes
- UTF-8 char = up to 4 bytes
- 767 / 4 = ~191 chars max
- varchar(255) with utf8mb4 → Need prefix length!

**Issue:**
```
UTF-8 full index on varchar(255):
  255 chars × 4 bytes = 1020 bytes > 767 byte limit
  → ERROR: Specified key too long; max key length is 767 bytes
```

**Affected Indexes:**
- Any full-length index on varchar columns

**Action Required Before Migration:**
1. ✓ Verify UTF-8 charset compatibility
2. ✓ Check if full-length indexes needed
3. ✓ Test migration doesn't hit index size limits

---

### 🟢 LOW ISSUE #1: Auto-Increment Starting Value

**Severity:** LOW  
**Impact:** ID sequence may differ  
**Files Affected:**
- All tables with `$table->id()`

**Problem:**
```php
$table->id();  // Auto-increment starting from 1
```

**SQLite Behavior:**
- Auto-increment starts from 1
- Sequence continues from last deleted ID + 1

**MySQL Behavior:**
- Auto-increment starts from 1
- After delete, next insert gets next sequence value
- But behavior differs with engine type

**Action Required:**
1. ✓ No action needed - compatible behavior

---

### 🟢 LOW ISSUE #2: Column Comment Handling

**Severity:** LOW  
**Impact:** Comments may be lost or formatted differently  
**Files Affected:**
- All migration files use `->comment()`

**Problem:**
```php
$table->string('name')->comment('Nama lengkap user');
```

**SQLite Behavior:**
- Comments stored in schema (can retrieve)
- Not enforced or visible in UI

**MySQL Behavior:**
- Comments stored in table comment
- Visible in INFORMATION_SCHEMA
- Preserved during migration

**Action Required:**
1. ✓ No action needed - safe to migrate

---

### 🟢 LOW ISSUE #3: Primary Key Type

**Severity:** LOW  
**Impact:** None if using standard `$table->id()`  
**Files Affected:**
- Most tables (using `$table->id()`)

**Problem:**
```php
$table->id();  // BigInteger unsigned auto-increment
```

**SQLite Behavior:**
- Uses INTEGER PRIMARY KEY AUTOINCREMENT
- Compatible with MySQL BIGINT UNSIGNED

**MySQL Behavior:**
- BIGINT UNSIGNED AUTO_INCREMENT
- Safe and compatible

**Action Required:**
1. ✓ No action needed - compatible

---

### 🟢 LOW ISSUE #4: Cascade Delete Behavior

**Severity:** LOW  
**Impact:** Should work the same in MySQL  
**Files Affected:**
- Multiple migration files with `->onDelete('cascade')`

**Problem:**
```php
->onDelete('cascade')
->onDelete('set null')
```

**SQLite Behavior:**
- If FK constraints enabled: Works
- If disabled: Silently ignored

**MySQL Behavior:**
- Always enforced
- Required for cascade to work: MySQL must have InnoDB engine

**Action Required:**
1. ✓ Verify InnoDB engine (not MyISAM)
2. ✓ No other action needed

---

## Detailed Risk Assessment by File

### File: `database/migrations/0001_01_01_000000_create_users_table.php`

| Issue | Risk | Line(s) | Details |
|-------|------|---------|---------|
| Enum casting | 🔴 CRITICAL | 18 | `role` enum may reject unknown values |
| Unique email case-sensitivity | 🟠 HIGH | 17 | Case handling differs |
| String length validation | 🟡 MEDIUM | 16-19 | May truncate > 255 chars |
| Password field length | 🟡 MEDIUM | 19 | Hashed passwords typically 60-65 chars (fits) |
| Timestamp precision | 🟠 HIGH | 21 | created_at/updated_at precision |

**Specific Checks Needed:**
- ✓ Verify no `role` values other than 'admin', 'mahasiswa'
- ✓ Check for emails with case duplicates
- ✓ Verify email length < 255
- ✓ Check password field length (should be OK)
- ✓ Check FK references to study_programs valid

---

### File: `database/migrations/2026_06_15_000005_create_debt_records_table.php`

| Issue | Risk | Line(s) | Details |
|-------|------|---------|---------|
| Enum casting (type) | 🔴 CRITICAL | 17 | Must be 'debt' or 'receivable' |
| Enum casting (status) | 🔴 CRITICAL | 24 | Must be 'pending', 'active', 'rejected', 'settled' |
| Foreign key constraints | 🟠 HIGH | 16-17 | May have orphaned records |
| DateTime precision | 🟠 HIGH | 19-23 | Timestamp precision loss |
| Numeric precision | 🟡 MEDIUM | 18 | Decimal(12,2) for currency |

**Specific Checks Needed:**
- ✓ Verify all `type` values: 'debt', 'receivable'
- ✓ Verify all `status` values are valid
- ✓ Check no orphaned creator_id/counterpart_id
- ✓ Verify transaction_date <= due_date logic
- ✓ Check decimal precision acceptable

---

### File: `database/migrations/2026_06_15_000006_create_debt_status_changes_table.php`

| Issue | Risk | Line(s) | Details |
|-------|------|---------|---------|
| Enum casting (old/new status) | 🔴 CRITICAL | 13-14 | Must be valid DebtStatus values |
| Foreign key to debt_records | 🟠 HIGH | 12 | May have orphaned records |
| Foreign key to users | 🟠 HIGH | 13 | May have orphaned records |

**Specific Checks Needed:**
- ✓ Verify all old_status/new_status valid
- ✓ Check all debt_record_id references exist
- ✓ Check all changed_by_user_id references exist

---

### File: `database/migrations/0001_01_01_000002_create_jobs_table.php`

| Issue | Risk | Line(s) | Details |
|-------|------|---------|---------|
| Integer overflow (2038) | 🟠 HIGH | 10-11 | unsignedInteger will overflow in 2038 |
| JSON payload handling | 🟠 HIGH | 8 | JSON string to MySQL JSON conversion |
| Job batches integer overflow | 🟠 HIGH | 15-19 | created_at, finished_at overflow risk |

**Specific Checks Needed:**
- ✓ Consider upgrading to BIGINT
- ✓ Verify JSON format valid
- ✓ Monitor for year 2038 issues

---

### File: `database/migrations/2026_06_15_000008_create_notifications_table.php`

| Issue | Risk | Line(s) | Details |
|-------|------|---------|---------|
| JSON column handling | 🟠 HIGH | 12 | `data` JSON may differ in null handling |
| Foreign keys | 🟠 HIGH | 11-12 | May have orphaned records |
| Unique index on composite | 🟡 MEDIUM | N/A | No composite unique, but index may hit size limit |

**Specific Checks Needed:**
- ✓ Verify all JSON in `data` column valid
- ✓ Check all user_id, notification_type_id, debt_record_id valid
- ✓ Verify composite index doesn't exceed size limit

---

### File: `database/migrations/2026_06_15_000010_create_reminder_logs_table.php`

| Issue | Risk | Line(s) | Details |
|-------|------|---------|---------|
| Unique composite constraint | 🟠 HIGH | 17 | May conflict if case-sensitive handling differs |
| Foreign keys | 🟠 HIGH | 11-12 | May have orphaned records |

**Specific Checks Needed:**
- ✓ Verify no duplicate (debt_record_id, user_id, days_before) combinations
- ✓ Check all debt_record_id, user_id valid

---

### File: `database/migrations/2026_06_15_000007_create_fcm_tokens_table.php`

| Issue | Risk | Line(s) | Details |
|-------|------|---------|---------|
| Unique token field | 🟠 HIGH | 15 | Case handling may differ |
| Boolean column | 🟡 MEDIUM | 14 | is_active type handling |
| Text token length | 🟡 MEDIUM | 15 | Token stored as text (unlimited in SQLite) |

**Specific Checks Needed:**
- ✓ Verify no duplicate tokens with case variation
- ✓ Check is_active only 0 or 1
- ✓ Verify token length < 65535 (text limit)

---

### File: `database/migrations/2026_06_15_000009_create_audit_logs_table.php`

| Issue | Risk | Line(s) | Details |
|-------|------|---------|---------|
| JSON columns | 🟠 HIGH | 10-11 | old_values/new_values JSON handling |
| Foreign key to users | 🟠 HIGH | 11 | May have orphaned records |

**Specific Checks Needed:**
- ✓ Verify all JSON valid in old_values, new_values
- ✓ Check all user_id valid references

---

## Pre-Migration Checklist

### Before Migration - DO THESE CHECKS:

#### Phase 1: Data Validation (CRITICAL)
- [ ] **Enum Values Audit**
  - [ ] Check all `users.role` values: only 'admin', 'mahasiswa'
  - [ ] Check all `debt_records.type` values: only 'debt', 'receivable'
  - [ ] Check all `debt_records.status` values: only 'pending', 'active', 'rejected', 'settled'
  - [ ] Check all `debt_status_changes.old_status`/`new_status` values
  - [ ] Query to verify:
    ```sql
    SELECT DISTINCT role FROM users;
    SELECT DISTINCT type FROM debt_records;
    SELECT DISTINCT status FROM debt_records;
    SELECT DISTINCT old_status, new_status FROM debt_status_changes;
    ```

- [ ] **Foreign Key Validation**
  - [ ] Check no orphaned `creator_id` in debt_records
  - [ ] Check no orphaned `counterpart_id` in debt_records
  - [ ] Check no orphaned `study_program_id` in users
  - [ ] Check no orphaned `user_id` in all dependent tables
  - [ ] Query to verify:
    ```sql
    SELECT * FROM debt_records WHERE creator_id NOT IN (SELECT id FROM users);
    SELECT * FROM debt_records WHERE counterpart_id NOT IN (SELECT id FROM users);
    SELECT * FROM users WHERE study_program_id NOT IN (SELECT id FROM study_programs);
    -- etc for all FKs
    ```

- [ ] **Unique Constraint Validation**
  - [ ] Check for duplicate emails (case-insensitive)
  - [ ] Check for duplicate NIMs (case-insensitive)
  - [ ] Check for duplicate study program codes
  - [ ] Check for duplicate FCM tokens
  - [ ] Query to verify:
    ```sql
    SELECT LOWER(email), COUNT(*) FROM users GROUP BY LOWER(email) HAVING COUNT(*) > 1;
    SELECT LOWER(nim), COUNT(*) FROM users WHERE nim IS NOT NULL GROUP BY LOWER(nim) HAVING COUNT(*) > 1;
    ```

#### Phase 2: Configuration & Preparation
- [ ] Create full database backup (SQLite)
- [ ] Export SQLite database to DBeaver
- [ ] Set up MySQL connection in DBeaver
- [ ] Create empty MySQL database `simpati` (or target name)
- [ ] Set MySQL charset to `utf8mb4` and collation to `utf8mb4_unicode_ci`

#### Phase 3: DBeaver Migration
- [ ] In DBeaver: Database → Migration Wizard
- [ ] Select SQLite as source
- [ ] Select MySQL as target
- [ ] Review migration mapping
- [ ] Verify enum handling in migration options
- [ ] Run test migration to staging environment first

#### Phase 4: Post-Migration Validation
- [ ] Verify all tables created
- [ ] Verify all rows transferred
- [ ] Verify foreign keys active
- [ ] Verify indexes created
- [ ] Run integrity checks:
  ```sql
  SELECT COUNT(*) FROM users;
  SELECT COUNT(*) FROM debt_records;
  -- etc for all tables
  ```

- [ ] Test application connections
- [ ] Run test suite against new MySQL DB

---

## Files Requiring Changes (Post-Audit)

### Configuration Files to Update

#### 1. `.env` File
**Current:**
```
DB_CONNECTION=sqlite
```

**After Migration:**
```
DB_CONNECTION=mysql
DB_HOST=<your-host>
DB_PORT=3306
DB_DATABASE=simpati
DB_USERNAME=<user>
DB_PASSWORD=<password>
```

#### 2. `config/database.php`
**Current:** Uses sqlite as default  
**After:** Update to use mysql

---

## Risk Summary Table

| Issue | Severity | Table(s) | Impact | Pre-Check | Post-Check |
|-------|----------|---------|--------|-----------|------------|
| Enum values invalid | 🔴 CRITICAL | users, debt_records, debt_status_changes | Migration fails | ✓ Validate all enum values | ✓ Test queries with invalid values |
| Orphaned FKs | 🟠 HIGH | debt_records, notifications, audit_logs, reminder_logs | Migration fails | ✓ Find orphaned records | ✓ Verify FKs enforced |
| DateTime precision | 🟠 HIGH | All tables with timestamps | Data loss | ✓ Check microsecond usage | ✓ Verify timestamp integrity |
| Email case duplicates | 🟠 HIGH | users | Constraint violation | ✓ Find case duplicates | ✓ Verify unique index works |
| JSON null handling | 🟠 HIGH | notifications, audit_logs, jobs | Data type mismatch | ✓ Verify JSON format | ✓ Test null handling |
| Year 2038 overflow | 🟠 HIGH | jobs, job_batches | Future failure | ✓ Plan for upgrades | ✓ Monitor timestamps |
| String truncation | 🟡 MEDIUM | All string columns | Data loss | ✓ Check max lengths | ✓ Test bulk operations |
| Index size limit | 🟡 MEDIUM | String indexes | Index creation fails | ✓ Calculate index sizes | ✓ Verify indexes created |
| Boolean type | 🟡 MEDIUM | fcm_tokens | Type coercion | ✓ Check is_active values | ✓ Test boolean queries |
| Case sensitivity | 🟡 MEDIUM | Multiple | Query results differ | ✓ Verify case usage | ✓ Test collation behavior |

---

## Recommended Action Plan

### ✅ Phase 1: Pre-Migration (Current Status)
**Time: 2-4 hours**
1. Run all validation queries (provided below)
2. Document any issues found
3. Create data cleanup scripts if needed
4. Create backup of SQLite database
5. Get MySQL credentials ready

### ✅ Phase 2: Migration Setup (Next)
**Time: 1-2 hours**
1. Create MySQL database with correct charset
2. Set up DBeaver MySQL connection
3. Configure migration settings
4. Run test migration to test environment

### ✅ Phase 3: Production Migration (After Validation)
**Time: 30 minutes - 1 hour**
1. Run migration wizard in DBeaver
2. Verify all data transferred
3. Run post-migration validation
4. Update `.env` file
5. Test application

### ✅ Phase 4: Cutover (After Validation)
**Time: 1-2 hours**
1. Update application configuration
2. Run migrations (if any pending)
3. Run seeders if needed
4. Full regression testing
5. Monitor application logs

---

## SQL Validation Queries

### Create file: `database/validation-queries.sql`

```sql
-- 1. CHECK ENUM VALUES IN USERS TABLE
SELECT 'users.role unique values' as check_name, 
       GROUP_CONCAT(DISTINCT role) as values,
       COUNT(DISTINCT role) as count
FROM users;

-- 2. CHECK ENUM VALUES IN DEBT_RECORDS
SELECT 'debt_records.type unique values' as check_name,
       GROUP_CONCAT(DISTINCT type) as values,
       COUNT(DISTINCT type) as count
FROM debt_records;

SELECT 'debt_records.status unique values' as check_name,
       GROUP_CONCAT(DISTINCT status) as values,
       COUNT(DISTINCT status) as count
FROM debt_records;

-- 3. CHECK FOREIGN KEY INTEGRITY - Debt Records
SELECT 'debt_records orphaned creator_id' as check_name,
       COUNT(*) as orphaned_count
FROM debt_records dr
WHERE dr.creator_id NOT IN (SELECT id FROM users);

SELECT 'debt_records orphaned counterpart_id' as check_name,
       COUNT(*) as orphaned_count
FROM debt_records dr
WHERE dr.counterpart_id NOT IN (SELECT id FROM users);

-- 4. CHECK DUPLICATE EMAILS (CASE-INSENSITIVE)
SELECT 'duplicate emails (case-insensitive)' as check_name,
       LOWER(email) as email,
       COUNT(*) as count
FROM users
GROUP BY LOWER(email)
HAVING COUNT(*) > 1;

-- 5. CHECK DUPLICATE NIMs (CASE-INSENSITIVE)
SELECT 'duplicate nims (case-insensitive)' as check_name,
       LOWER(nim) as nim,
       COUNT(*) as count
FROM users
WHERE nim IS NOT NULL
GROUP BY LOWER(nim)
HAVING COUNT(*) > 1;

-- 6. CHECK REMINDER LOG UNIQUENESS
SELECT 'reminder_logs duplicate combinations' as check_name,
       debt_record_id, user_id, days_before,
       COUNT(*) as count
FROM reminder_logs
GROUP BY debt_record_id, user_id, days_before
HAVING COUNT(*) > 1;

-- 7. CHECK STRING LENGTHS
SELECT 'max email length' as check_name,
       MAX(LENGTH(email)) as max_length
FROM users;

SELECT 'max password length' as check_name,
       MAX(LENGTH(password)) as max_length
FROM users;

-- 8. CHECK NULL VALUES IN NON-NULLABLE FK
SELECT 'null creator_id count' as check_name,
       COUNT(*) as null_count
FROM debt_records
WHERE creator_id IS NULL;

-- 9. CHECK JSON FORMAT
SELECT 'audit_logs with invalid json' as check_name,
       COUNT(*) as count
FROM audit_logs
WHERE old_values IS NOT NULL
  AND NOT JSON_VALID(old_values);

-- 10. DATA SUMMARY
SELECT 'table row counts' as check_name;
SELECT 'users' as table_name, COUNT(*) as count FROM users;
SELECT 'study_programs' as table_name, COUNT(*) as count FROM study_programs;
SELECT 'debt_records' as table_name, COUNT(*) as count FROM debt_records;
SELECT 'notifications' as table_name, COUNT(*) as count FROM notifications;
SELECT 'audit_logs' as table_name, COUNT(*) as count FROM audit_logs;
```

---

## Conclusion

**Current Status:** ⚠️ **Ready for migration WITH precautions**

The SIMPATI project CAN be successfully migrated from SQLite to MySQL, but requires:

1. ✅ Data validation before migration (6-8 hours preparation)
2. ✅ Schema adjustments for timestamp precision (optional but recommended)
3. ✅ Integer overflow handling for year 2038 (recommended for future-proofing)
4. ✅ Post-migration verification (2-3 hours)

**NO CODE CHANGES NEEDED** - Just data validation and careful migration execution.

---

**Report Generated:** 2026-06-15  
**Audit Status:** COMPLETE - Ready for migration planning  
**Next Step:** Run validation queries and address findings  

