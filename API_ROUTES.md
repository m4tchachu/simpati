# 📋 API Routes Documentation - SIMPATI v1

## Overview

**Base URL:** `http://localhost:8000/api/v1`

**Versioning:** v1 (API prefix `/api/v1`)

**Authentication:** Laravel Sanctum (Bearer Token)

**Content-Type:** `application/json`

---

## 🔐 Authentication

### Get Token
```bash
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "student@example.com",
  "password": "password"
}

Response 200:
{
  "message": "Login berhasil",
  "data": {
    "user": { ... },
    "token": "1|abc123def456..."
  }
}
```

### Use Token in Requests
```bash
Authorization: Bearer 1|abc123def456...
```

### Logout
```bash
POST /api/v1/auth/logout
Authorization: Bearer TOKEN
```

---

## 📊 Route Structure & Organization

### Middleware Stack

```
┌─────────────────────────────────────────────┐
│ API v1 Routes                               │
├─────────────────────────────────────────────┤
│ ├─ PUBLIC ROUTES (No Auth)                  │
│ │  ├─ /auth/login                          │
│ │  ├─ /auth/check-email                    │
│ │  └─ /auth/check-nim                      │
│ │                                           │
│ └─ AUTHENTICATED (auth:sanctum)             │
│    ├─ /auth/* (Current User)                │
│    ├─ /admin/* (role:admin)                 │
│    │  └─ /students/* (CRUD)                │
│    ├─ /mahasiswa/* (role:mahasiswa)         │
│    │  └─ /debts/* (Full Lifecycle)         │
│    └─ /shared/* (Both Roles)                │
│       ├─ /notifications/*                  │
│       └─ /dashboard/*                      │
└─────────────────────────────────────────────┘
```

---

## 🔓 PUBLIC ROUTES (No Authentication)

### Auth Controller

#### 1. Login
```bash
POST /api/v1/auth/login

Request:
{
  "email": "student@example.com",
  "password": "password"
}

Response 200:
{
  "message": "Login berhasil",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "student@example.com",
      "role": "mahasiswa",
      "nim": "A123456"
    },
    "token": "1|abc123..."
  }
}

Response 401: Unauthorized
```

#### 2. Check Email Availability
```bash
POST /api/v1/auth/check-email

Request:
{
  "email": "newstudent@example.com"
}

Response 200:
{
  "email": "newstudent@example.com",
  "exists": false
}
```

#### 3. Check NIM Availability
```bash
POST /api/v1/auth/check-nim

Request:
{
  "nim": "A654321"
}

Response 200:
{
  "nim": "A654321",
  "exists": false
}
```

---

## 🔑 AUTHENTICATED ROUTES

All routes below require: `Authorization: Bearer TOKEN`

### Auth Routes (Current User)

#### 1. Get Current User
```bash
GET /api/v1/auth/me

Response 200:
{
  "message": "User data retrieved",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "student@example.com",
    "role": "mahasiswa",
    "study_program": { ... }
  }
}
```

#### 2. Logout
```bash
POST /api/v1/auth/logout

Response 200:
{
  "message": "Logout berhasil"
}
```

#### 3. Refresh Token
```bash
POST /api/v1/auth/refresh-token

Response 200:
{
  "message": "Token refreshed successfully",
  "data": {
    "token": "1|new_token_here..."
  }
}
```

#### 4. Update FCM Token
```bash
POST /api/v1/auth/fcm-token

Request:
{
  "fcm_token": "firebase_token_here",
  "device_name": "iPhone 13"
}

Response 200:
{
  "message": "FCM token updated successfully"
}
```

#### 5. Change Password
```bash
POST /api/v1/auth/change-password

Request:
{
  "current_password": "old_password",
  "new_password": "new_password",
  "new_password_confirmation": "new_password"
}

Response 200:
{
  "message": "Password changed successfully"
}

Response 422: Validation Error
```

---

## 👨‍💼 ADMIN ROUTES (role:admin)

### Student Management

#### 1. List Students (Paginated)
```bash
GET /api/v1/students?page=1&per_page=15&search=john&study_program_id=1&sort=name&order=asc

Query Parameters:
- page (int, optional): Page number (default: 1)
- per_page (int, optional): Items per page (default: 15, max: 100)
- search (string, optional): Search by name, nim, or email
- study_program_id (int, optional): Filter by study program
- sort (string, optional): Sort field (name|nim|email|created_at)
- order (string, optional): Sort order (asc|desc)

Response 200:
{
  "message": "Students retrieved successfully",
  "data": [
    {
      "id": 1,
      "nim": "A123456",
      "name": "John Doe",
      "email": "john@example.com",
      "study_program": { ... }
    },
    ...
  ],
  "pagination": {
    "total": 50,
    "per_page": 15,
    "current_page": 1,
    "last_page": 4,
    "from": 1,
    "to": 15
  }
}
```

#### 2. Search Students
```bash
GET /api/v1/students/search?q=john&limit=10

Query Parameters:
- q (string, required): Search query (min 2 chars)
- limit (int, optional): Results limit (default: 10, max: 50)

Response 200:
{
  "message": "Students search results",
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "nim": "A123456",
      "email": "john@example.com",
      "study_program": "Informatika"
    }
  ]
}
```

#### 3. Create Student
```bash
POST /api/v1/students

Request:
{
  "nim": "A654321",
  "name": "Jane Smith",
  "email": "jane@example.com",
  "password": "password",
  "password_confirmation": "password",
  "study_program_id": 1
}

Response 201:
{
  "message": "Student created successfully",
  "data": { ... }
}

Response 422: Validation Error
```

#### 4. Get Student
```bash
GET /api/v1/students/{id}

Response 200:
{
  "message": "Student retrieved successfully",
  "data": {
    "id": 1,
    "nim": "A123456",
    "name": "John Doe",
    "email": "john@example.com",
    "study_program": { ... },
    "active_fcm_tokens_count": 2
  }
}
```

#### 5. Update Student
```bash
PUT /api/v1/students/{id}

Request:
{
  "name": "John Updated",
  "email": "john.updated@example.com",
  "study_program_id": 2
}

Response 200:
{
  "message": "Student updated successfully",
  "data": { ... }
}
```

#### 6. Delete Student
```bash
DELETE /api/v1/students/{id}

Response 200:
{
  "message": "Student deleted successfully"
}
```

#### 7. Get Student Statistics
```bash
GET /api/v1/students/{id}/stats

Response 200:
{
  "message": "Student statistics retrieved",
  "data": {
    "total_debt": 150000,
    "total_receivable": 200000,
    "active_debt_count": 2,
    "active_receivable_count": 3,
    "pending_count": 1,
    "overdue_count": 0
  }
}
```

#### 8. Export Students
```bash
POST /api/v1/students/export?search=john&study_program_id=1

Response 200:
{
  "message": "Students exported successfully",
  "data": [
    {
      "id": 1,
      "nim": "A123456",
      "name": "John Doe",
      "email": "john@example.com",
      "study_program": "Informatika",
      "created_at": "2026-06-15T10:00:00Z"
    }
  ],
  "count": 1
}
```

---

## 💳 MAHASISWA ROUTES (role:mahasiswa)

### Debt Records

#### 1. List Debts (Paginated)
```bash
GET /api/v1/debts?page=1&per_page=15&type=debt&status=active&sort=created_at&order=desc

Query Parameters:
- page (int, optional): Page number
- per_page (int, optional): Items per page (max: 100)
- type (string, optional): debt | receivable
- status (string, optional): pending | active | rejected | settled
- sort (string, optional): created_at | due_date | amount
- order (string, optional): asc | desc

Response 200:
{
  "message": "Debt records retrieved successfully",
  "data": [
    {
      "id": 1,
      "type": "debt",
      "type_label": "Hutang",
      "amount": 50000.00,
      "status": "active",
      "status_label": "Aktif",
      "status_color": "info",
      "due_date": "2026-06-30",
      "creator": { ... },
      "counterpart": { ... }
    }
  ],
  "pagination": { ... }
}
```

#### 2. Get Overdue Debts
```bash
GET /api/v1/debts/overdue

Response 200:
{
  "message": "Overdue debts retrieved successfully",
  "data": [ ... ],
  "count": 2
}
```

#### 3. Get Upcoming Debts
```bash
GET /api/v1/debts/upcoming?days=7

Query Parameters:
- days (int, optional): Days ahead (default: 7, max: 90)

Response 200:
{
  "message": "Upcoming debts retrieved successfully",
  "data": [ ... ],
  "count": 3
}
```

#### 4. Search Debts
```bash
GET /api/v1/debts/search?q=hutang&limit=10

Query Parameters:
- q (string, required): Search query
- limit (int, optional): Results limit (max: 50)

Response 200:
{
  "message": "Debt records search results",
  "data": [
    {
      "id": 1,
      "type": "Hutang",
      "amount": 50000,
      "description": "Pinjaman untuk kuliah",
      "status": "Aktif",
      "due_date": "2026-06-30",
      "creator": "John Doe",
      "counterpart": "Jane Smith"
    }
  ]
}
```

#### 5. Create Debt Record
```bash
POST /api/v1/debts

Request:
{
  "counterpart_id": 2,
  "type": "debt",
  "amount": 50000.00,
  "description": "Pinjaman untuk kuliah",
  "transaction_date": "2026-06-15",
  "due_date": "2026-06-30"
}

Response 201:
{
  "message": "Debt record created successfully",
  "data": {
    "id": 1,
    "type": "debt",
    "amount": 50000.00,
    "status": "pending",
    "creator": { ... },
    "counterpart": { ... }
  }
}

Response 422: Validation Error
```

#### 6. Get Debt Record
```bash
GET /api/v1/debts/{id}

Response 200:
{
  "message": "Debt record retrieved successfully",
  "data": { ... }
}

Response 403: Forbidden (Not creator or counterpart)
```

#### 7. Update Debt Record (Creator, Pending Only)
```bash
PUT /api/v1/debts/{id}

Request:
{
  "amount": 60000.00,
  "due_date": "2026-07-15"
}

Response 200:
{
  "message": "Debt record updated successfully",
  "data": { ... }
}

Response 422: Cannot update non-pending debt
```

#### 8. Delete Debt Record (Creator, Pending Only)
```bash
DELETE /api/v1/debts/{id}

Response 200:
{
  "message": "Debt record deleted successfully"
}

Response 422: Cannot delete non-pending debt
```

#### 9. Confirm Debt (Counterpart, Pending Only)
```bash
POST /api/v1/debts/{id}/confirm

Response 200:
{
  "message": "Debt record confirmed successfully",
  "data": {
    "id": 1,
    "status": "active",
    "status_label": "Aktif",
    "confirmed_at": "2026-06-15T10:30:00Z"
  }
}

Response 422: Cannot confirm non-pending debt
```

#### 10. Reject Debt (Counterpart, Pending Only)
```bash
POST /api/v1/debts/{id}/reject

Request:
{
  "rejection_reason": "Sudah dibayar sebelumnya"
}

Response 200:
{
  "message": "Debt record rejected successfully",
  "data": {
    "id": 1,
    "status": "rejected",
    "status_label": "Ditolak",
    "rejected_at": "2026-06-15T10:30:00Z",
    "rejection_reason": "Sudah dibayar sebelumnya"
  }
}

Response 422: Cannot reject non-pending debt
```

#### 11. Settle Debt (Creator or Counterpart, Active Only)
```bash
POST /api/v1/debts/{id}/settle

Response 200:
{
  "message": "Debt record settled successfully",
  "data": {
    "id": 1,
    "status": "settled",
    "status_label": "Lunas",
    "settled_at": "2026-06-15T10:30:00Z"
  }
}

Response 422: Cannot settle non-active debt
```

#### 12. Get Debt History
```bash
GET /api/v1/debts/{id}/history

Response 200:
{
  "message": "Debt history retrieved successfully",
  "data": [
    {
      "id": 1,
      "old_status": "pending",
      "old_status_label": "Menunggu Konfirmasi",
      "new_status": "active",
      "new_status_label": "Aktif",
      "reason": null,
      "changed_by": {
        "id": 2,
        "name": "Jane Smith"
      },
      "created_at": "2026-06-15T10:30:00Z"
    }
  ]
}
```

#### 13. Get Debt Statistics
```bash
GET /api/v1/debts/stats

Response 200:
{
  "message": "Debt statistics retrieved",
  "data": {
    "total_debt": 150000,
    "total_receivable": 200000,
    "active_debt_count": 2,
    "active_receivable_count": 3,
    "pending_count": 1,
    "rejected_count": 0,
    "settled_count": 5,
    "overdue_count": 1
  }
}
```

---

## 🔔 SHARED ROUTES (Authenticated Users)

### Notifications

#### 1. List Notifications
```bash
GET /api/v1/notifications?page=1&per_page=15&type=debt_created&read=false

Query Parameters:
- page (int, optional): Page number
- per_page (int, optional): Items per page
- type (string, optional): Notification type code
- read (boolean, optional): Filter by read status (true|false)

Response 200:
{
  "message": "Notifications retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Transaksi Hutang Baru",
      "message": "John Doe telah membuat transaksi hutang...",
      "type": {
        "id": 1,
        "code": "debt_created",
        "name": "Debt Created"
      },
      "is_read": false,
      "created_at": "2026-06-15T10:00:00Z"
    }
  ],
  "pagination": { ... }
}
```

#### 2. Get Unread Count
```bash
GET /api/v1/notifications/unread-count

Response 200:
{
  "message": "Unread count retrieved",
  "data": {
    "unread_count": 5
  }
}
```

#### 3. Get Unread Notifications
```bash
GET /api/v1/notifications/unread

Response 200:
{
  "message": "Unread notifications retrieved",
  "data": [ ... ],
  "count": 5
}
```

#### 4. Get Notification Stats
```bash
GET /api/v1/notifications/stats

Response 200:
{
  "message": "Notification statistics retrieved",
  "data": {
    "unread_count": 3,
    "total_count": 25,
    "by_type": [
      {
        "type": "Debt Created",
        "count": 10
      },
      {
        "type": "Debt Confirmed",
        "count": 8
      }
    ]
  }
}
```

#### 5. Mark Notification as Read
```bash
POST /api/v1/notifications/{id}/read

Response 200:
{
  "message": "Notification marked as read",
  "data": { ... }
}
```

#### 6. Mark Notification as Unread
```bash
POST /api/v1/notifications/{id}/unread

Response 200:
{
  "message": "Notification marked as unread",
  "data": { ... }
}
```

#### 7. Mark All as Read
```bash
POST /api/v1/notifications/mark-all-read

Response 200:
{
  "message": "All notifications marked as read",
  "data": {
    "marked_count": 5
  }
}
```

#### 8. Delete Notification
```bash
DELETE /api/v1/notifications/{id}

Response 200:
{
  "message": "Notification deleted successfully"
}
```

#### 9. Delete All Notifications
```bash
DELETE /api/v1/notifications

Response 200:
{
  "message": "All notifications deleted successfully",
  "data": {
    "deleted_count": 25
  }
}
```

---

### Dashboard

#### 1. Get Complete Dashboard
```bash
GET /api/v1/dashboard

Response 200:
{
  "message": "Dashboard data retrieved successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "mahasiswa"
    },
    "debt_stats": {
      "total_debt": 150000,
      "total_receivable": 200000,
      "net_balance": 50000,
      ...
    },
    "notifications": {
      "unread_count": 3,
      "total_count": 25,
      "by_type": [ ... ]
    },
    "recent_transactions": [ ... ],
    "upcoming_debts": [ ... ],
    "overdue_debts": [ ... ],
    "summary_cards": [ ... ],
    "charts_data": { ... }
  }
}
```

#### 2. Get Debt Stats Only
```bash
GET /api/v1/dashboard/debt-stats

Response 200:
{
  "message": "Debt statistics retrieved",
  "data": { ... }
}
```

#### 3. Get Notification Summary
```bash
GET /api/v1/dashboard/notification-summary

Response 200:
{
  "message": "Notification summary retrieved",
  "data": { ... }
}
```

#### 4. Get Recent Transactions
```bash
GET /api/v1/dashboard/recent-transactions?limit=5

Query Parameters:
- limit (int, optional): Number of transactions (default: 5)

Response 200:
{
  "message": "Recent transactions retrieved",
  "data": [ ... ],
  "count": 5
}
```

#### 5. Get Upcoming Debts
```bash
GET /api/v1/dashboard/upcoming-debts?days=7

Query Parameters:
- days (int, optional): Days ahead (default: 7)

Response 200:
{
  "message": "Upcoming debts retrieved",
  "data": [ ... ],
  "count": 3
}
```

#### 6. Get Overdue Debts
```bash
GET /api/v1/dashboard/overdue-debts

Response 200:
{
  "message": "Overdue debts retrieved",
  "data": [ ... ],
  "count": 2
}
```

#### 7. Get Summary Cards
```bash
GET /api/v1/dashboard/summary-cards

Response 200:
{
  "message": "Summary cards retrieved",
  "data": [
    {
      "title": "Total Hutang",
      "value": "Rp 150.000",
      "color": "danger",
      "icon": "trending-down",
      "change": {
        "percentage": 10.5,
        "trend": "up"
      }
    },
    ...
  ]
}
```

#### 8. Get Charts Data
```bash
GET /api/v1/dashboard/charts-data

Response 200:
{
  "message": "Charts data retrieved",
  "data": {
    "debt_status_distribution": [
      {
        "name": "Aktif",
        "value": 2,
        "color": "info"
      },
      ...
    ],
    "debt_type_distribution": [
      {
        "name": "Hutang",
        "value": 150000,
        "count": 5,
        "color": "#dc3545"
      },
      ...
    ],
    "monthly_trend": [
      {
        "month": "Jan",
        "debt": 50000,
        "receivable": 100000,
        "net": 50000
      },
      ...
    ],
    "top_counterparts": [
      {
        "id": 2,
        "name": "Jane Smith",
        "transaction_count": 5,
        "total_amount": 200000
      }
    ]
  }
}
```

---

## 🔄 Route Naming Convention

All routes have automatic names for route generation:

```php
// Example: Get the "show student" route
route('students.show', ['student' => 1])
// Returns: /api/v1/students/1

// Example: Get the "list debts" route
route('debts.index')
// Returns: /api/v1/debts

// Example: Generate URL
url(route('notifications.index'))
// Returns: https://localhost:8000/api/v1/notifications
```

---

## 📝 HTTP Methods

| Method | Purpose | Body |
|--------|---------|------|
| GET | Retrieve data | ❌ No |
| POST | Create data / Custom actions | ✅ Yes |
| PUT | Update entire resource | ✅ Yes |
| DELETE | Remove resource | ❌ No |

---

## 🚫 Error Responses

### 400 - Bad Request
```json
{
  "message": "Bad request",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

### 401 - Unauthorized
```json
{
  "message": "Unauthenticated"
}
```

### 403 - Forbidden
```json
{
  "message": "Forbidden. This action requires: admin"
}
```

### 404 - Not Found
```json
{
  "message": "Endpoint not found",
  "status": 404
}
```

### 422 - Validation Error
```json
{
  "message": "Unprocessable Content",
  "errors": {
    "email": ["Email field is required"],
    "password": ["Password must be at least 6 characters"]
  }
}
```

### 500 - Server Error
```json
{
  "message": "Failed to retrieve dashboard data",
  "error": "Detailed error message"
}
```

---

## 🔐 Authorization Rules

| Resource | Create | Read | Update | Delete |
|----------|--------|------|--------|--------|
| **Students** | Admin | Admin | Admin | Admin |
| **Debts** | Mahasiswa (creator) | Creator, Counterpart, Admin | Creator (pending) | Creator (pending) |
| **Debt Actions** | - | - | - | - |
| - Confirm | - | - | Counterpart (pending) | - |
| - Reject | - | - | Counterpart (pending) | - |
| - Settle | - | - | Creator/Counterpart (active) | - |
| **Notifications** | System | Own | - | Own |
| **Dashboard** | - | Own | - | - |

---

## 🛠️ Testing with cURL

### Login
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "student@example.com",
    "password": "password"
  }'
```

### Create Debt
```bash
curl -X POST http://localhost:8000/api/v1/debts \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "counterpart_id": 2,
    "type": "debt",
    "amount": 50000.00,
    "description": "Pinjaman untuk kuliah",
    "transaction_date": "2026-06-15",
    "due_date": "2026-06-30"
  }'
```

### List Debts
```bash
curl -X GET "http://localhost:8000/api/v1/debts?page=1&per_page=10&type=debt&status=active" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 🎯 API Features

✅ **Versioning** - /api/v1 prefix
✅ **Authentication** - Sanctum Bearer tokens
✅ **Authorization** - Role-based (admin, mahasiswa)
✅ **Pagination** - Cursor-based with metadata
✅ **Filtering** - Multiple filter parameters
✅ **Searching** - Full-text search support
✅ **Sorting** - Customizable sort fields & order
✅ **Error Handling** - Consistent error responses
✅ **Rate Limiting** - Can be added via middleware
✅ **Resource Formatting** - API Resources for consistency

---
