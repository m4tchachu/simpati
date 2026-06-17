# 🔍 AUDIT LENGKAP REST API - PROJECT SIMPATI

**Tanggal Audit:** 17 Juni 2026  
**Sistem:** Laravel 12 Backend API  
**Versi API:** v1  
**Auditor:** Senior Backend Auditor  

---

## 📊 RINGKASAN EKSEKUTIF

### Status Keseluruhan: ⚠️ **PERLU PERBAIKAN URGENT**

| Kategori | Status | Issues |
|----------|--------|--------|
| **Routes** | ✅ Good | Route structure well-organized |
| **Controllers** | ⚠️ Medium | Inconsistent error handling, weak authorization checks |
| **Validation** | ⚠️ Medium | Unusual FormRequest patterns, insufficient input validation |
| **Models & Relationships** | ⚠️ Medium | Good structure but eager loading issues |
| **Authorization** | 🔴 **CRITICAL** | Authorization scattered across layers, admin override checks missing |
| **Performance** | 🔴 **CRITICAL** | Multiple N+1 query issues, in-memory collection operations |
| **API Resources** | ⚠️ Medium | Response format inconsistencies |
| **Security** | ✅ Good | Sanctum token implementation solid |

---

## 🔴 CRITICAL ISSUES (MUST FIX IMMEDIATELY)

### 1. **Authorization Scattered Across Layers** 
**Severity:** 🔴 **CRITICAL**  
**Priority:** P0  
**Files Affected:**
- [app/Http/Requests/ConfirmDebtRecordRequest.php](app/Http/Requests/ConfirmDebtRecordRequest.php#L1)
- [app/Http/Requests/RejectDebtRecordRequest.php](app/Http/Requests/RejectDebtRecordRequest.php#L1)
- [app/Http/Requests/SettleDebtRecordRequest.php](app/Http/Requests/SettleDebtRecordRequest.php#L1)
- [app/Http/Controllers/Api/DebtRecordController.php](app/Http/Controllers/Api/DebtRecordController.php#L170)
- [app/Policies/DebtRecordPolicy.php](app/Policies/DebtRecordPolicy.php#L50)

**Problem:**
```php
// ❌ WRONG: Authorization in FormRequest
public function authorize(): bool {
    $debtRecord = $this->route('debtRecord');
    return $this->user() && $debtRecord->counterpart_id === $this->user()->id;
}

// ✅ CORRECT: Authorization through Policy in Controller
$this->authorize('confirm', $debtRecord);
```

**Issues:**
- Authorization in FormRequest can be bypassed if request validation is skipped
- Dual authorization (FormRequest + Controller) creates confusion
- Admin authorization checks missing entirely
- No clear indication that admin can override actions

**Risk:** Users could potentially perform unauthorized actions if FormRequest validation is bypassed through different code paths.

**Recommendation:**
- Remove authorization logic from FormRequest
- Move ALL authorization to Controller via Policy
- Add admin override checks to Policy methods
- Ensure consistent authorization flow

---

### 2. **Severe Query N+1 in DashboardService**
**Severity:** 🔴 **CRITICAL**  
**Priority:** P0  
**Files Affected:**
- [app/Services/DashboardService.php](app/Services/DashboardService.php#L40)
- [app/Models/User.php](app/Models/User.php#L85)

**Problem:**
```php
// ❌ CRITICAL N+1 ISSUE
public function getDebtStats(User $user): array {
    $allDebts = $user->getAllDebts();  // Loads ALL debts in-memory
    
    // Then collection operations (inefficient for large datasets)
    $totalDebt = $allDebts->where('type', DebtType::DEBT)->sum('amount');
    $activeDebtAmount = $allDebts->where('type', DebtType::DEBT)
        ->where('status', DebtStatus::ACTIVE)->sum('amount');
    // ... more collection operations
}

// In User model:
public function getAllDebts(): Collection {
    return $this->createdDebts->merge($this->receivedDebts);
    // Loads ALL debts without pagination/limits
}
```

**Issues:**
- Loading ALL debt records into memory via `getAllDebts()`
- For user with 1000+ debts, this loads entire collections
- Collection operations (where, sum, count) on large arrays in PHP
- Applied multiple times in DashboardService methods
- No pagination or limits

**Performance Impact:**
- User with 1000 debts: ~1000+ queries if relationships not eager loaded
- Memory spike loading entire collections
- Slow dashboard load times
- Potential timeout on high-traffic endpoints

**Recommendation:**
```php
// ✅ BETTER: Use database queries
public function getDebtStats(User $user): array {
    return [
        'total_debt' => DebtRecord::where('creator_id', $user->id)
            ->orWhere('counterpart_id', $user->id)
            ->where('type', DebtType::DEBT)
            ->sum('amount'),
        // Use single query with aggregation
    ];
}
```

---

### 3. **Missing Admin Authorization Checks for Mahasiswa-Only Routes**
**Severity:** 🔴 **CRITICAL**  
**Priority:** P0  
**Files Affected:**
- [routes/api.php](routes/api.php#L130)
- [app/Policies/DebtRecordPolicy.php](app/Policies/DebtRecordPolicy.php#L45)
- [app/Policies/StudentPolicy.php](app/Policies/StudentPolicy.php#L55)

**Problem:**
```php
// ❌ In routes/api.php
Route::middleware('role:mahasiswa')->group(function () {
    Route::prefix('debts')->group(function () {
        Route::post('{debtRecord}/confirm', [DebtRecordController::class, 'confirm'])
    });
});

// Policy allows admin, but route middleware blocks it
public function confirm(User $user, DebtRecord $debtRecord): bool {
    if ($user->isAdmin()) {  // Admin CAN confirm per policy
        return true;
    }
    return $debtRecord->counterpart_id === $user->id;
}
```

**Issue:**
- Route middleware `role:mahasiswa` blocks admin from accessing debt endpoints
- But Policy explicitly allows admin to perform actions
- Contradiction: admin can't reach endpoint but policy says they can
- Search endpoint allows BOTH roles to search

**Risk:**
- Admin management of debt records impossible despite policy allowance
- Inconsistent authorization model

**Recommendation:**
```php
// Option 1: Create admin route group
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('debts/{debtRecord}/confirm-admin', [DebtRecordController::class, 'confirmAdmin']);
});

// Option 2: Remove role middleware, rely only on Policy
Route::middleware('auth:sanctum')->group(function () {
    Route::post('debts/{debtRecord}/confirm', [DebtRecordController::class, 'confirm']);
});

// Then Policy handles authorization consistently
```

---

### 4. **Unusual FormRequest Validation Pattern - Dummy Merge**
**Severity:** 🔴 **CRITICAL** (Code Quality/Bug Risk)  
**Priority:** P1  
**Files Affected:**
- [app/Http/Requests/ConfirmDebtRecordRequest.php](app/Http/Requests/ConfirmDebtRecordRequest.php#L26)
- [app/Http/Requests/RejectDebtRecordRequest.php](app/Http/Requests/RejectDebtRecordRequest.php#L30)
- [app/Http/Requests/SettleDebtRecordRequest.php](app/Http/Requests/SettleDebtRecordRequest.php#L25)

**Problem:**
```php
// ❌ ANTI-PATTERN: Merge dummy value to trigger custom validation
public function prepareForValidation(): void {
    $this->merge([
        'status' => true,  // Dummy value to trigger validation rule
    ]);
}

public function rules(): array {
    return [
        'status' => [
            function ($attribute, $value, $fail) {
                if ($debtRecord->status !== DebtStatus::PENDING) {
                    $fail('Cannot confirm non-pending record');
                }
            },
        ],
    ];
}
```

**Issues:**
- Unconventional pattern - merging fake data into request
- `$value` in custom validation is always `true` (the merged dummy)
- Status validation not based on actual request data
- Highly confusing for other developers
- Not following Laravel conventions

**Better Approach:**
```php
// ✅ CORRECT: Status validation in authorize() or controller
public function authorize(): bool {
    $debtRecord = $this->route('debtRecord');
    return $debtRecord->status === DebtStatus::PENDING;
}

// Or in controller:
if ($debtRecord->status !== DebtStatus::PENDING) {
    return response()->json(['error' => 'Record must be pending'], 422);
}
```

---

## ⚠️ HIGH PRIORITY ISSUES (FIX SOON)

### 5. **Query N+1 in Search Endpoint**
**Severity:** 🟠 **HIGH**  
**Priority:** P1  
**Files Affected:**
- [app/Services/DebtRecordService.php](app/Services/DebtRecordService.php#L357)

**Problem:**
```php
public function searchDebtRecords(User $user, string $query, int $limit = 10): array {
    return DebtRecord::where(function ($q) use ($user) {
        $q->where('creator_id', $user->id)
            ->orWhere('counterpart_id', $user->id);
    })->where(function ($q) use ($query) {
        $q->where('description', 'like', "%{$query}%")
            ->orWhereHas('creator', fn ($q) => $q->where('name', 'like', "%{$query}%"))
            ->orWhereHas('counterpart', fn ($q) => $q->where('name', 'like', "%{$query}%"));
    })->with('creator', 'counterpart')  // ✅ Has eager loading
     ->limit($limit)
     ->get()
     ->map(function ($debt) {  // ❌ Additional query per result
         return [
             'id' => $debt->id,
             'type' => $debt->type->label(),  // If type is relation, N+1 here
             'status' => $debt->status->label(),
         ];
     })
     ->toArray();
}
```

**Issue:**
- `whereHas()` on creator/counterpart relationships
- Each `->where()` adds subquery
- Mapping performs additional operations

**Recommendation:**
- Optimize queries using indexes
- Limit search scope
- Add result count limits

---

### 6. **Missing Input Validation in Multiple Endpoints**
**Severity:** 🟠 **HIGH**  
**Priority:** P1  
**Files Affected:**
- [app/Http/Controllers/Api/DebtRecordController.php](app/Http/Controllers/Api/DebtRecordController.php#L318)
- [app/Http/Controllers/Api/StudentController.php](app/Http/Controllers/Api/StudentController.php#L55)

**Problem:**
```php
// ❌ stats() endpoint - no input validation
public function stats(Request $request): JsonResponse {
    $stats = $this->debtRecordService->getDebtStats($request->user());
    // No validation of input parameters
}

// ❌ No limit on pagination results
public function index(Request $request): JsonResponse {
    $filters = $request->validate([
        'per_page' => 'nullable|integer|min:1|max:100',  // ✅ Has max
    ]);
    // But some endpoints don't have this
}
```

**Missing Validations:**
- `/debts/stats` - no pagination
- `/debts/search` - limit max:50 but no min/max for relevance
- `/dashboard/recent-transactions` - limit validation exists
- `/dashboard/upcoming-debts` - days parameter max:90 could be tighter

**Recommendation:**
- Add consistent pagination validation to all list endpoints
- Add rate limiting for expensive queries
- Add input sanitization

---

### 7. **Inconsistent Error Response Format**
**Severity:** 🟠 **HIGH**  
**Priority:** P1  
**Files Affected:**
- [app/Http/Controllers/Api/](app/Http/Controllers/Api/)

**Problem:**
```php
// ❌ Format 1: With error field
return response()->json([
    'message' => 'Login gagal',
    'error' => $e->getMessage(),
], 401);

// ❌ Format 2: Just message
return response()->json([
    'message' => 'Unauthorized',
], 403);

// ❌ Format 3: No message
return response()->json([
    'message' => 'Failed to create student',
    'error' => $e->getMessage(),
], 422);

// ✅ Better: Consistent format
return response()->json([
    'success' => false,
    'message' => 'Failed to create student',
    'errors' => [
        'field' => ['error message'],
    ],
], 422);
```

**Issues:**
- Success responses have `message` + `data`
- Error responses have `message` + `error` (inconsistent)
- Some use error array, some use error string
- No consistent HTTP status codes
- No error codes/types for client error handling

**Recommendation:**
- Create consistent response format
- Add error codes (e.g., `DEBT_ALREADY_SETTLED`)
- Use proper HTTP status codes

---

### 8. **Policy Methods Called But Authorization in FormRequest**
**Severity:** 🟠 **HIGH**  
**Priority:** P1  
**Files Affected:**
- [app/Http/Controllers/Api/DebtRecordController.php](app/Http/Controllers/Api/DebtRecordController.php#L170)
- [app/Http/Requests/ConfirmDebtRecordRequest.php](app/Http/Requests/ConfirmDebtRecordRequest.php#L8)

**Problem:**
```php
// ❌ DOUBLE authorization
public function confirm(ConfirmDebtRecordRequest $request, DebtRecord $debtRecord): JsonResponse {
    // FormRequest ALREADY checked authorize()
    // Then Controller checks again (redundant but good pattern)
    $this->authorize('confirm', $debtRecord);
    
    // But FormRequest might fail for different reason
}

// If FormRequest::authorize() returns false, Laravel never reaches Controller
```

**Issue:**
- FormRequest authorization takes precedence
- Controller authorization never reached if FormRequest fails
- Confusing for developers

**Recommendation:**
- Choose ONE authorization pattern
- FormRequest: Just validation
- Controller: Authorization via Policy

---

### 9. **Missing Authorization Check for Delete Debt**
**Severity:** 🟠 **HIGH**  
**Priority:** P1  
**Files Affected:**
- [app/Http/Controllers/Api/DebtRecordController.php](app/Http/Controllers/Api/DebtRecordController.php#L125)

**Problem:**
```php
public function destroy(DebtRecord $debtRecord, Request $request): JsonResponse {
    $this->authorize('delete', $debtRecord);  // ✅ Good
    
    try {
        $this->debtRecordService->deleteDebtRecord($debtRecord->id, $request->user());
        // ...
    }
}

// But UpdateDebtRecordRequest doesn't call authorize() method
// UpdateDebtRecordRequest::authorize() checks in FormRequest
// This inconsistency is confusing
```

**Issue:**
- Update uses FormRequest authorize()
- Delete uses Controller authorize()
- Inconsistent pattern

---

### 10. **No Soft Deletes Implementation**
**Severity:** 🟠 **HIGH**  
**Priority:** P2  
**Files Affected:**
- [app/Models/DebtRecord.php](app/Models/DebtRecord.php#L1)
- [app/Models/User.php](app/Models/User.php#L1)

**Problem:**
- Deleting debt records permanently removes them
- No audit trail of deleted records
- Cannot restore accidentally deleted data
- Violates compliance requirements

**Recommendation:**
```php
// Add soft deletes
class DebtRecord extends Model {
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];
}

// Then queries automatically exclude deleted
// Create migration to add deleted_at column
```

---

## 🟡 MEDIUM PRIORITY ISSUES

### 11. **API Resource Efficiency - Multiple Load Checks**
**Severity:** 🟡 **MEDIUM**  
**Priority:** P2  
**Files Affected:**
- [app/Http/Resources/DebtRecordResource.php](app/Http/Resources/DebtRecordResource.php#L15)
- [app/Http/Resources/UserResource.php](app/Http/Resources/UserResource.php#L14)

**Problem:**
```php
'creator' => $this->whenLoaded('creator', fn () => [
    'id' => $this->creator->id,
    'name' => $this->creator->name,
    // If creator not loaded, these lines never execute
    // But if later code tries to access $this->creator, N+1 happens
]),
```

**Issue:**
- `whenLoaded()` is good, but ensure relationships always eager-loaded
- If relationship not loaded, field is omitted (could confuse clients)

---

### 12. **Inconsistent Pagination Response Structure**
**Severity:** 🟡 **MEDIUM**  
**Priority:** P2  
**Files Affected:**
- [app/Http/Controllers/Api/DebtRecordController.php](app/Http/Controllers/Api/DebtRecordController.php#L24)
- [app/Http/Controllers/Api/StudentController.php](app/Http/Controllers/Api/StudentController.php#L24)

**Problem:**
```php
// Format 1: Detailed pagination
return response()->json([
    'message' => 'Debts retrieved',
    'data' => DebtRecordResource::collection($debts),
    'pagination' => [
        'total' => $debts->total(),
        'per_page' => $debts->perPage(),
        'current_page' => $debts->currentPage(),
        'last_page' => $debts->lastPage(),
        'from' => $debts->firstItem(),
        'to' => $debts->lastItem(),
    ],
], 200);

// Format 2: Just count
return response()->json([
    'message' => 'Overdue debts',
    'data' => DebtRecordResource::collection($overdues),
    'count' => $overdues->count(),  // ❌ Different from pagination
], 200);
```

**Issue:**
- Some endpoints use `pagination` object
- Some use `count` field
- Inconsistent for client implementation

---

### 13. **Missing Offset/Limit Queries**
**Severity:** 🟡 **MEDIUM**  
**Priority:** P2  
**Files Affected:**
- [app/Services/DebtRecordService.php](app/Services/DebtRecordService.php#L390)

**Problem:**
```php
// ❌ No limit on results
public function getDebtHistory(int $debtRecordId): Collection {
    return DebtStatusChange::where('debt_record_id', $debtRecordId)
        ->with('changedByUser')
        ->orderBy('created_at', 'desc')
        ->get();  // Could return 1000+ records
}

// Should add pagination or limit
```

---

### 14. **Missing Null Coalescing in Service Methods**
**Severity:** 🟡 **MEDIUM**  
**Priority:** P2  
**Files Affected:**
- [app/Services/DashboardService.php](app/Services/DashboardService.php#L115)

**Problem:**
```php
// ❌ Assumes relationships exist
'creator' => $debt->creator->name,  // If creator soft-deleted, error!
'counterpart' => $debt->counterpart->name,  // Could be null

// ✅ Better
'creator' => $debt->creator?->name ?? 'Unknown',
```

---

## 🔵 LOW PRIORITY ISSUES

### 15. **Mixed Language in Validation Messages**
**Severity:** 🔵 **LOW**  
**Priority:** P3  
**Files Affected:**
- All request files have Indonesian error messages
- But some controller exceptions in English

**Recommendation:**
- Choose one language (prefer English for public APIs)
- Or use localization (Laravel trans())

---

### 16. **Missing API Documentation**
**Severity:** 🔵 **LOW**  
**Priority:** P3  
**Files Affected:**
- No OpenAPI/Swagger docs
- No error code documentation
- No response schema documentation

**Recommendation:**
- Generate OpenAPI specs
- Document error codes
- Create API documentation

---

### 17. **Unused Import in Controllers**
**Severity:** 🔵 **LOW**  
**Priority:** P3  
**Files Affected:**
- Various controller files

**Recommendation:**
- Clean up unused imports

---

## 📈 SUMMARY TABLE

| Issue # | Title | Severity | Priority | Type | Impact |
|---------|-------|----------|----------|------|--------|
| 1 | Authorization Scattered | 🔴 CRITICAL | P0 | Security | High |
| 2 | Query N+1 Dashboard | 🔴 CRITICAL | P0 | Performance | High |
| 3 | Admin Auth Missing | 🔴 CRITICAL | P0 | Security | High |
| 4 | Dummy Merge Pattern | 🔴 CRITICAL | P1 | Code Quality | Medium |
| 5 | N+1 Search | 🟠 HIGH | P1 | Performance | Medium |
| 6 | Missing Validation | 🟠 HIGH | P1 | Validation | Medium |
| 7 | Error Responses | 🟠 HIGH | P1 | API Design | Low |
| 8 | Double Auth Check | 🟠 HIGH | P1 | Code Quality | Low |
| 9 | Delete Auth Inconsistent | 🟠 HIGH | P1 | Security | Low |
| 10 | No Soft Deletes | 🟠 HIGH | P2 | Data Integrity | Medium |
| 11 | Resource Efficiency | 🟡 MEDIUM | P2 | Performance | Low |
| 12 | Pagination Inconsistent | 🟡 MEDIUM | P2 | API Design | Low |
| 13 | Missing Limits | 🟡 MEDIUM | P2 | Performance | Low |
| 14 | Null Coalescing | 🟡 MEDIUM | P2 | Robustness | Low |
| 15 | Language Mix | 🔵 LOW | P3 | Maintenance | Very Low |
| 16 | No Docs | 🔵 LOW | P3 | Maintenance | Very Low |
| 17 | Unused Imports | 🔵 LOW | P3 | Maintenance | Very Low |

---

## 🎯 RECOMMENDED FIXES (PRIORITY ORDER)

### Phase 1: CRITICAL FIXES (Week 1)
```
1. Consolidate authorization into Policy layer
   - Remove FormRequest authorize()
   - Move all checks to Controller + Policy
   
2. Fix Dashboard N+1 queries
   - Replace getAllDebts() with database queries
   - Use aggregation queries (SUM, COUNT) in DB
   
3. Fix Admin authorization routing
   - Create separate admin endpoints or
   - Remove role middleware, use Policy only
```

### Phase 2: HIGH PRIORITY FIXES (Week 2)
```
4. Remove dummy merge pattern from FormRequests
   - Use proper validation
   - Move status checks to controller/policy
   
5. Add input validation to all endpoints
   - Standardize pagination max values
   - Add rate limiting
   
6. Standardize error responses
   - Create ErrorResponse class
   - Use consistent format across all endpoints
```

### Phase 3: MEDIUM PRIORITY FIXES (Week 3-4)
```
7. Add soft deletes to critical models
   
8. Optimize search queries
   - Add indexes
   - Optimize whereHas() queries
   
9. Add API documentation
   - Generate OpenAPI specs
   - Document error codes
```

---

## ✅ WORKING CORRECTLY

### What's Good About This API:
1. ✅ Route structure well-organized with proper grouping
2. ✅ Sanctum token implementation solid
3. ✅ Event-driven notification system well-designed
4. ✅ Form validation comprehensive
5. ✅ Enum usage for status/type (type-safe)
6. ✅ Audit logging implemented
7. ✅ Policy-based authorization exists
8. ✅ Service layer separation of concerns
9. ✅ API Resources used for response formatting
10. ✅ Relationships properly defined in models

---

## 🔐 SECURITY ASSESSMENT

| Category | Status | Notes |
|----------|--------|-------|
| Authentication | ✅ Secure | Sanctum tokens with expiration |
| Authorization | 🔴 Issues | Scattered across layers |
| Input Validation | ⚠️ Needs Work | Inconsistent across endpoints |
| Injection | ✅ Safe | Uses Eloquent ORM, parameterized queries |
| Rate Limiting | ❌ Missing | No rate limiting implemented |
| CORS | ⚠️ Check | Need to verify CORS configuration |
| HTTPS | ✅ Assumed | Should be enforced in production |

---

## 📋 IMPLEMENTATION CHECKLIST

### Authorization Consolidation
- [ ] Audit all FormRequest authorize() methods
- [ ] Move authorization to Policy methods
- [ ] Ensure Controller calls authorize()
- [ ] Create admin-specific policy methods
- [ ] Test authorization for all endpoints

### Performance Optimization
- [ ] Refactor Dashboard queries
- [ ] Add database indexes
- [ ] Implement query caching where needed
- [ ] Add pagination limits
- [ ] Profile endpoints for N+1 issues

### Code Quality
- [ ] Remove dummy merge patterns
- [ ] Standardize error handling
- [ ] Standardize response formats
- [ ] Add null coalescing operators
- [ ] Implement soft deletes

### Testing
- [ ] Add authorization tests
- [ ] Add performance tests
- [ ] Add integration tests
- [ ] Test edge cases

---

## 📞 NEXT STEPS

1. **Review** this audit report with the team
2. **Prioritize** fixes based on business impact
3. **Create** tickets in issue tracker
4. **Assign** developers to fix critical issues
5. **Test** thoroughly after each fix
6. **Deploy** to staging before production

---

**End of Audit Report**
