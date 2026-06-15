-- SQLite to MySQL Migration - Validation Queries
-- Run these on SQLite BEFORE migration to identify data issues
-- File: database/validation-queries.sql

-- ============================================================================
-- 1. ENUM VALUE VALIDATION
-- ============================================================================

-- Check users.role values (should only be: admin, mahasiswa)
SELECT 'ENUM CHECK: users.role' as test_name,
       role as value,
       COUNT(*) as count
FROM users
GROUP BY role
ORDER BY role;

-- Check debt_records.type values (should only be: debt, receivable)
SELECT 'ENUM CHECK: debt_records.type' as test_name,
       type as value,
       COUNT(*) as count
FROM debt_records
GROUP BY type
ORDER BY type;

-- Check debt_records.status values (should only be: pending, active, rejected, settled)
SELECT 'ENUM CHECK: debt_records.status' as test_name,
       status as value,
       COUNT(*) as count
FROM debt_records
GROUP BY status
ORDER BY status;

-- Check debt_status_changes.old_status (should only be valid DebtStatus values)
SELECT 'ENUM CHECK: debt_status_changes.old_status' as test_name,
       old_status as value,
       COUNT(*) as count
FROM debt_status_changes
GROUP BY old_status
ORDER BY old_status;

-- Check debt_status_changes.new_status
SELECT 'ENUM CHECK: debt_status_changes.new_status' as test_name,
       new_status as value,
       COUNT(*) as count
FROM debt_status_changes
GROUP BY new_status
ORDER BY new_status;

-- ============================================================================
-- 2. FOREIGN KEY INTEGRITY CHECKS
-- ============================================================================

-- Check debt_records.creator_id references
SELECT 'FK CHECK: debt_records.creator_id ORPHANED' as test_name,
       COUNT(*) as orphaned_count
FROM debt_records dr
WHERE dr.creator_id NOT IN (SELECT id FROM users);

-- Check debt_records.counterpart_id references
SELECT 'FK CHECK: debt_records.counterpart_id ORPHANED' as test_name,
       COUNT(*) as orphaned_count
FROM debt_records dr
WHERE dr.counterpart_id NOT IN (SELECT id FROM users);

-- Check users.study_program_id references
SELECT 'FK CHECK: users.study_program_id ORPHANED' as test_name,
       COUNT(*) as orphaned_count
FROM users u
WHERE u.study_program_id IS NOT NULL
  AND u.study_program_id NOT IN (SELECT id FROM study_programs);

-- Check debt_status_changes.debt_record_id references
SELECT 'FK CHECK: debt_status_changes.debt_record_id ORPHANED' as test_name,
       COUNT(*) as orphaned_count
FROM debt_status_changes dsc
WHERE dsc.debt_record_id NOT IN (SELECT id FROM debt_records);

-- Check debt_status_changes.changed_by_user_id references
SELECT 'FK CHECK: debt_status_changes.changed_by_user_id ORPHANED' as test_name,
       COUNT(*) as orphaned_count
FROM debt_status_changes dsc
WHERE dsc.changed_by_user_id NOT IN (SELECT id FROM users);

-- Check notifications.user_id references
SELECT 'FK CHECK: notifications.user_id ORPHANED' as test_name,
       COUNT(*) as orphaned_count
FROM notifications n
WHERE n.user_id NOT IN (SELECT id FROM users);

-- Check notifications.notification_type_id references
SELECT 'FK CHECK: notifications.notification_type_id ORPHANED' as test_name,
       COUNT(*) as orphaned_count
FROM notifications n
WHERE n.notification_type_id NOT IN (SELECT id FROM notification_types);

-- Check notifications.debt_record_id references (nullable)
SELECT 'FK CHECK: notifications.debt_record_id ORPHANED' as test_name,
       COUNT(*) as orphaned_count
FROM notifications n
WHERE n.debt_record_id IS NOT NULL
  AND n.debt_record_id NOT IN (SELECT id FROM debt_records);

-- Check audit_logs.user_id references
SELECT 'FK CHECK: audit_logs.user_id ORPHANED' as test_name,
       COUNT(*) as orphaned_count
FROM audit_logs al
WHERE al.user_id NOT IN (SELECT id FROM users);

-- Check reminder_logs.debt_record_id references
SELECT 'FK CHECK: reminder_logs.debt_record_id ORPHANED' as test_name,
       COUNT(*) as orphaned_count
FROM reminder_logs rl
WHERE rl.debt_record_id NOT IN (SELECT id FROM debt_records);

-- Check reminder_logs.user_id references
SELECT 'FK CHECK: reminder_logs.user_id ORPHANED' as test_name,
       COUNT(*) as orphaned_count
FROM reminder_logs rl
WHERE rl.user_id NOT IN (SELECT id FROM users);

-- Check fcm_tokens.user_id references
SELECT 'FK CHECK: fcm_tokens.user_id ORPHANED' as test_name,
       COUNT(*) as orphaned_count
FROM fcm_tokens ft
WHERE ft.user_id NOT IN (SELECT id FROM users);

-- Check personal_access_tokens.tokenable_id references
SELECT 'FK CHECK: personal_access_tokens tokenable ORPHANED' as test_name,
       COUNT(*) as orphaned_count
FROM personal_access_tokens pat
WHERE pat.tokenable_type = 'App\\Models\\User'
  AND pat.tokenable_id NOT IN (SELECT id FROM users);

-- ============================================================================
-- 3. UNIQUE CONSTRAINT CHECKS
-- ============================================================================

-- Check for duplicate emails (considering case-insensitivity)
SELECT 'UNIQUE CHECK: users.email duplicates (case-insensitive)' as test_name,
       LOWER(email) as email,
       GROUP_CONCAT(id) as user_ids,
       COUNT(*) as count
FROM users
GROUP BY LOWER(email)
HAVING COUNT(*) > 1;

-- Check for duplicate emails (case-sensitive)
SELECT 'UNIQUE CHECK: users.email duplicates (case-sensitive)' as test_name,
       email,
       GROUP_CONCAT(id) as user_ids,
       COUNT(*) as count
FROM users
GROUP BY email
HAVING COUNT(*) > 1;

-- Check for duplicate NIMs (considering case-insensitivity)
SELECT 'UNIQUE CHECK: users.nim duplicates (case-insensitive)' as test_name,
       LOWER(nim) as nim,
       GROUP_CONCAT(id) as user_ids,
       COUNT(*) as count
FROM users
WHERE nim IS NOT NULL
GROUP BY LOWER(nim)
HAVING COUNT(*) > 1;

-- Check for duplicate NIMs (case-sensitive)
SELECT 'UNIQUE CHECK: users.nim duplicates (case-sensitive)' as test_name,
       nim,
       GROUP_CONCAT(id) as user_ids,
       COUNT(*) as count
FROM users
WHERE nim IS NOT NULL
GROUP BY nim
HAVING COUNT(*) > 1;

-- Check for duplicate study program codes
SELECT 'UNIQUE CHECK: study_programs.code duplicates' as test_name,
       code,
       GROUP_CONCAT(id) as program_ids,
       COUNT(*) as count
FROM study_programs
GROUP BY code
HAVING COUNT(*) > 1;

-- Check for duplicate notification type codes
SELECT 'UNIQUE CHECK: notification_types.code duplicates' as test_name,
       code,
       GROUP_CONCAT(id) as type_ids,
       COUNT(*) as count
FROM notification_types
GROUP BY code
HAVING COUNT(*) > 1;

-- Check for duplicate FCM tokens (case-sensitive)
SELECT 'UNIQUE CHECK: fcm_tokens.token duplicates' as test_name,
       token,
       GROUP_CONCAT(id) as token_ids,
       COUNT(*) as count
FROM fcm_tokens
GROUP BY token
HAVING COUNT(*) > 1;

-- Check reminder_logs unique constraint (debt_record_id, user_id, days_before)
SELECT 'UNIQUE CHECK: reminder_logs composite duplicates' as test_name,
       debt_record_id,
       user_id,
       days_before,
       GROUP_CONCAT(id) as reminder_ids,
       COUNT(*) as count
FROM reminder_logs
GROUP BY debt_record_id, user_id, days_before
HAVING COUNT(*) > 1;

-- Check personal_access_tokens unique token
SELECT 'UNIQUE CHECK: personal_access_tokens.token duplicates' as test_name,
       token,
       GROUP_CONCAT(id) as token_ids,
       COUNT(*) as count
FROM personal_access_tokens
GROUP BY token
HAVING COUNT(*) > 1;

-- ============================================================================
-- 4. STRING LENGTH VALIDATION
-- ============================================================================

-- Check email field lengths
SELECT 'STRING LENGTH: users.email' as test_name,
       MAX(LENGTH(email)) as max_length,
       MIN(LENGTH(email)) as min_length,
       AVG(LENGTH(email)) as avg_length
FROM users;

-- Check password field lengths
SELECT 'STRING LENGTH: users.password' as test_name,
       MAX(LENGTH(password)) as max_length,
       MIN(LENGTH(password)) as min_length,
       AVG(LENGTH(password)) as avg_length
FROM users;

-- Check NIM field lengths
SELECT 'STRING LENGTH: users.nim' as test_name,
       MAX(LENGTH(nim)) as max_length,
       MIN(LENGTH(nim)) as min_length,
       AVG(LENGTH(nim)) as avg_length
FROM users
WHERE nim IS NOT NULL;

-- Check FCM token lengths
SELECT 'STRING LENGTH: fcm_tokens.token' as test_name,
       MAX(LENGTH(token)) as max_length,
       MIN(LENGTH(token)) as min_length,
       AVG(LENGTH(token)) as avg_length
FROM fcm_tokens;

-- Check personal access token lengths
SELECT 'STRING LENGTH: personal_access_tokens.token' as test_name,
       MAX(LENGTH(token)) as max_length,
       MIN(LENGTH(token)) as min_length,
       AVG(LENGTH(token)) as avg_length
FROM personal_access_tokens;

-- Check IP address field lengths
SELECT 'STRING LENGTH: sessions.ip_address' as test_name,
       MAX(LENGTH(ip_address)) as max_length,
       MIN(LENGTH(ip_address)) as min_length,
       AVG(LENGTH(ip_address)) as avg_length
FROM sessions
WHERE ip_address IS NOT NULL;

-- Check user agent field lengths
SELECT 'STRING LENGTH: sessions.user_agent' as test_name,
       MAX(LENGTH(user_agent)) as max_length,
       MIN(LENGTH(user_agent)) as min_length,
       AVG(LENGTH(user_agent)) as avg_length
FROM sessions
WHERE user_agent IS NOT NULL;

-- ============================================================================
-- 5. NULL VALUE CHECKS
-- ============================================================================

-- Check NULL values in non-nullable FK columns
SELECT 'NULL CHECK: debt_records.creator_id' as test_name,
       COUNT(*) as null_count
FROM debt_records
WHERE creator_id IS NULL;

SELECT 'NULL CHECK: debt_records.counterpart_id' as test_name,
       COUNT(*) as null_count
FROM debt_records
WHERE counterpart_id IS NULL;

SELECT 'NULL CHECK: fcm_tokens.is_active' as test_name,
       COUNT(*) as null_count
FROM fcm_tokens
WHERE is_active IS NULL;

-- ============================================================================
-- 6. DATETIME/TIMESTAMP CHECKS
-- ============================================================================

-- Check earliest and latest timestamps in system
SELECT 'DATETIME RANGE: users.created_at' as test_name,
       MIN(created_at) as earliest,
       MAX(created_at) as latest,
       COUNT(*) as count
FROM users;

SELECT 'DATETIME RANGE: debt_records.created_at' as test_name,
       MIN(created_at) as earliest,
       MAX(created_at) as latest,
       COUNT(*) as count
FROM debt_records;

-- Check for timestamps in future (data quality check)
SELECT 'DATETIME VALIDATION: users with future created_at' as test_name,
       COUNT(*) as count
FROM users
WHERE created_at > datetime('now');

-- Check for NULL timestamps where not allowed
SELECT 'NULL CHECK: debt_records.transaction_date' as test_name,
       COUNT(*) as null_count
FROM debt_records
WHERE transaction_date IS NULL;

SELECT 'NULL CHECK: debt_records.due_date' as test_name,
       COUNT(*) as null_count
FROM debt_records
WHERE due_date IS NULL;

-- ============================================================================
-- 7. DATA INTEGRITY CHECKS
-- ============================================================================

-- Check for past due dates without settlement
SELECT 'DATA QUALITY: overdue unsettled debts' as test_name,
       COUNT(*) as count
FROM debt_records
WHERE due_date < datetime('now')
  AND status IN ('pending', 'active');

-- Check debt record amounts are positive
SELECT 'DATA QUALITY: debt_records with invalid amounts' as test_name,
       COUNT(*) as count
FROM debt_records
WHERE amount <= 0;

-- Check for invalid audit log actions
SELECT 'DATA QUALITY: audit_logs.action values' as test_name,
       action,
       COUNT(*) as count
FROM audit_logs
GROUP BY action;

-- ============================================================================
-- 8. TABLE ROW COUNTS (BASELINE)
-- ============================================================================

SELECT 'TABLE COUNT: users' as table_name,
       COUNT(*) as row_count
FROM users;

SELECT 'TABLE COUNT: study_programs' as table_name,
       COUNT(*) as row_count
FROM study_programs;

SELECT 'TABLE COUNT: notification_types' as table_name,
       COUNT(*) as row_count
FROM notification_types;

SELECT 'TABLE COUNT: debt_records' as table_name,
       COUNT(*) as row_count
FROM debt_records;

SELECT 'TABLE COUNT: debt_status_changes' as table_name,
       COUNT(*) as row_count
FROM debt_status_changes;

SELECT 'TABLE COUNT: fcm_tokens' as table_name,
       COUNT(*) as row_count
FROM fcm_tokens;

SELECT 'TABLE COUNT: notifications' as table_name,
       COUNT(*) as row_count
FROM notifications;

SELECT 'TABLE COUNT: audit_logs' as table_name,
       COUNT(*) as row_count
FROM audit_logs;

SELECT 'TABLE COUNT: reminder_logs' as table_name,
       COUNT(*) as row_count
FROM reminder_logs;

SELECT 'TABLE COUNT: personal_access_tokens' as table_name,
       COUNT(*) as row_count
FROM personal_access_tokens;

SELECT 'TABLE COUNT: jobs' as table_name,
       COUNT(*) as row_count
FROM jobs;

SELECT 'TABLE COUNT: failed_jobs' as table_name,
       COUNT(*) as row_count
FROM failed_jobs;

SELECT 'TABLE COUNT: cache' as table_name,
       COUNT(*) as row_count
FROM cache;

-- ============================================================================
-- 9. BOOLEAN VALUE CHECKS
-- ============================================================================

SELECT 'BOOLEAN CHECK: fcm_tokens.is_active values' as test_name,
       is_active as value,
       COUNT(*) as count
FROM fcm_tokens
GROUP BY is_active;

-- ============================================================================
-- 10. COLLATION & CHARACTER SET CHECK
-- ============================================================================

-- SQLite doesn't have explicit collation, but we can check string patterns
SELECT 'CHARSET CHECK: users with non-ASCII in name' as test_name,
       COUNT(*) as count
FROM users
WHERE name GLOB '*[^[:ascii:]]*';

SELECT 'CHARSET CHECK: users with non-ASCII in email' as test_name,
       COUNT(*) as count
FROM users
WHERE email GLOB '*[^[:ascii:]]*';

-- ============================================================================
-- SUMMARY REPORT
-- ============================================================================

SELECT '═════════════════════════════════════════════════' as report;
SELECT 'Migration Readiness Check Complete' as status;
SELECT 'Review results above for any ISSUES or DUPLICATES' as note;
SELECT 'No results from a check = No issues found' as info;
