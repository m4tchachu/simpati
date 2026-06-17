<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DebtRecordController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/**
 * API v1 Routes
 * Base URL: /api/v1
 */
Route::prefix('v1')->group(function () {

    /**
     * =========================================================================
     * PUBLIC ROUTES - No Authentication Required
     * =========================================================================
     */
    Route::prefix('auth')->group(function () {
        // Login endpoint
        Route::post('login', [AuthController::class, 'login'])->name('auth.login');

        // Check email availability
        Route::post('check-email', [AuthController::class, 'checkEmail'])->name('auth.checkEmail');

        // Check NIM availability
        Route::post('check-nim', [AuthController::class, 'checkNim'])->name('auth.checkNim');
    });

    /**
     * =========================================================================
     * AUTHENTICATED ROUTES - Requires Sanctum Token
     * Middleware: auth:sanctum
     * =========================================================================
     */
    Route::middleware('auth:sanctum')->group(function () {

        /**
         * Authentication Routes
         * Prefix: /auth
         */
        Route::prefix('auth')->group(function () {
            // Get current user
            Route::get('me', [AuthController::class, 'me'])->name('auth.me');

            // Logout
            Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');

            // Refresh token
            Route::post('refresh-token', [AuthController::class, 'refreshToken'])->name('auth.refreshToken');

            // Update FCM token
            Route::post('fcm-token', [AuthController::class, 'updateFcmToken'])->name('auth.updateFcmToken');

            // Change password
            Route::post('change-password', [AuthController::class, 'changePassword'])->name('auth.changePassword');
        });

        /**
         * =====================================================================
         * ADMIN ROUTES - Requires Admin Role
         * Middleware: role:admin
         * =====================================================================
         */
        Route::middleware('role:admin')->group(function () {

            /**
             * Student Management Routes
             * Prefix: /students
             * Authorization: Admin only
             */
            Route::prefix('students')->name('students.')->group(function () {
                // List students with pagination
                Route::get('/', [StudentController::class, 'index'])
                    ->name('index');

                // Search students
                Route::get('search', [StudentController::class, 'search'])
                    ->name('search');

                // Export students
                Route::post('export', [StudentController::class, 'export'])
                    ->name('export');

                // Create student
                Route::post('/', [StudentController::class, 'store'])
                    ->name('store');

                // Show single student
                Route::get('{student}', [StudentController::class, 'show'])
                    ->name('show')
                    ->where('student', '[0-9]+');

                // Get student statistics
                Route::get('{student}/stats', [StudentController::class, 'stats'])
                    ->name('stats')
                    ->where('student', '[0-9]+');

                // Update student
                Route::put('{student}', [StudentController::class, 'update'])
                    ->name('update')
                    ->where('student', '[0-9]+');

                // Delete student
                Route::delete('{student}', [StudentController::class, 'destroy'])
                    ->name('destroy')
                    ->where('student', '[0-9]+');
            });
        });

        /**
         * =====================================================================
         * DEBT RECORD ROUTES - Requires Authentication
         * Authorization: Handled by Policy (creator, counterpart, admin)
         * Middleware: auth:sanctum (no role restriction)
         * =====================================================================
         */
        Route::prefix('debts')->name('debts.')->group(function () {
            // List user's debts
            Route::get('/', [DebtRecordController::class, 'index'])
                ->name('index');

            // Get overdue debts
            Route::get('overdue', [DebtRecordController::class, 'overdue'])
                ->name('overdue');

            // Get upcoming debts
            Route::get('upcoming', [DebtRecordController::class, 'upcoming'])
                ->name('upcoming');

            // Search debts
            Route::get('search', [DebtRecordController::class, 'search'])
                ->name('search');

            // Get debt statistics
            Route::get('stats', [DebtRecordController::class, 'stats'])
                ->name('stats');

            // Create debt record
            Route::post('/', [DebtRecordController::class, 'store'])
                ->name('store');

            // Show single debt record
            Route::get('{debtRecord}', [DebtRecordController::class, 'show'])
                ->name('show')
                ->where('debtRecord', '[0-9]+');

            // Get debt history
            Route::get('{debtRecord}/history', [DebtRecordController::class, 'history'])
                ->name('history')
                ->where('debtRecord', '[0-9]+');

            // Update debt record (creator, pending only)
            Route::put('{debtRecord}', [DebtRecordController::class, 'update'])
                ->name('update')
                ->where('debtRecord', '[0-9]+');

            // Delete debt record (creator, pending only)
            Route::delete('{debtRecord}', [DebtRecordController::class, 'destroy'])
                ->name('destroy')
                ->where('debtRecord', '[0-9]+');

            // Confirm debt record (counterpart, pending only)
            Route::post('{debtRecord}/confirm', [DebtRecordController::class, 'confirm'])
                ->name('confirm')
                ->where('debtRecord', '[0-9]+');

            // Reject debt record (counterpart, pending only)
            Route::post('{debtRecord}/reject', [DebtRecordController::class, 'reject'])
                ->name('reject')
                ->where('debtRecord', '[0-9]+');

            // Settle debt record (creator or counterpart, active only)
            Route::post('{debtRecord}/settle', [DebtRecordController::class, 'settle'])
                ->name('settle')
                ->where('debtRecord', '[0-9]+');
        });

        /**
         * =====================================================================
         * MAHASISWA ROUTES - Requires Mahasiswa Role
         * Middleware: role:mahasiswa
         * =====================================================================
         */
        Route::middleware('role:mahasiswa')->group(function () {

        /**
         * =====================================================================
         * SHARED ROUTES - Available for Authenticated Users (Both Admin & Mahasiswa)
         * =====================================================================
         */

        /**
         * Notification Routes
         * Prefix: /notifications
         * Authorization: Own notifications only
         */
        Route::prefix('notifications')->name('notifications.')->group(function () {
            // List notifications
            Route::get('/', [NotificationController::class, 'index'])
                ->name('index');

            // Get unread count
            Route::get('unread-count', [NotificationController::class, 'unreadCount'])
                ->name('unreadCount');

            // Get unread notifications
            Route::get('unread', [NotificationController::class, 'unread'])
                ->name('unread');

            // Get notification statistics
            Route::get('stats', [NotificationController::class, 'stats'])
                ->name('stats');

            // Mark all as read
            Route::post('mark-all-read', [NotificationController::class, 'markAllAsRead'])
                ->name('markAllAsRead');

            // Mark notification as read
            Route::post('{id}/read', [NotificationController::class, 'markAsRead'])
                ->name('markAsRead')
                ->where('id', '[0-9]+');

            // Mark notification as unread
            Route::post('{id}/unread', [NotificationController::class, 'markAsUnread'])
                ->name('markAsUnread')
                ->where('id', '[0-9]+');

            // Delete notification
            Route::delete('{id}', [NotificationController::class, 'destroy'])
                ->name('destroy')
                ->where('id', '[0-9]+');

            // Delete all notifications
            Route::delete('/', [NotificationController::class, 'deleteAll'])
                ->name('deleteAll');
        });

        /**
         * Dashboard Routes
         * Prefix: /dashboard
         * Authorization: Own data only
         */
        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            // Get complete dashboard
            Route::get('/', [DashboardController::class, 'index'])
                ->name('index');

            // Get debt statistics
            Route::get('debt-stats', [DashboardController::class, 'debtStats'])
                ->name('debtStats');

            // Get notification summary
            Route::get('notification-summary', [DashboardController::class, 'notificationSummary'])
                ->name('notificationSummary');

            // Get recent transactions
            Route::get('recent-transactions', [DashboardController::class, 'recentTransactions'])
                ->name('recentTransactions');

            // Get upcoming debts
            Route::get('upcoming-debts', [DashboardController::class, 'upcomingDebts'])
                ->name('upcomingDebts');

            // Get overdue debts
            Route::get('overdue-debts', [DashboardController::class, 'overdueDebts'])
                ->name('overdueDebts');

            // Get summary cards
            Route::get('summary-cards', [DashboardController::class, 'summaryCards'])
                ->name('summaryCards');

            // Get charts data
            Route::get('charts-data', [DashboardController::class, 'chartsData'])
                ->name('chartsData');
        });

    });
});

/**
 * =========================================================================
 * FALLBACK ROUTE - Not found
 * =========================================================================
 */
Route::fallback(function () {
    return response()->json([
        'message' => 'Endpoint not found',
        'status' => 404,
    ], 404);
});
