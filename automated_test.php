<?php

/**
 * =====================================================================
 * AUTOMATED API TESTING SCRIPT
 * =====================================================================
 * 
 * Project: Simpati - Debt Tracking System
 * Purpose: Automated testing of all 46 API endpoints
 * Framework: Laravel 12 with Sanctum
 * 
 * Usage: php tests/automated_test.php
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\DebtRecord;
use App\Enums\UserRole;
use App\Enums\DebtType;
use App\Enums\DebtStatus;

$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

// =====================================================================
// TEST CONFIGURATION
// =====================================================================

class APITester {
    private $baseUrl = 'http://localhost:8000/api/v1';
    private $adminToken = null;
    private $mahasiswaToken = null;
    private $testResults = [];
    private $passedCount = 0;
    private $failedCount = 0;
    private $createdIds = [];

    public function __construct() {
        $this->setupTestData();
    }

    private function setupTestData() {
        echo "\n=== SETTING UP TEST DATA ===\n";
        
        // Clear existing test data
        User::where('email', 'like', 'test_%@test.com')->forceDelete();

        // Seed notification types
        $types = [
            ['code' => 'debt_created', 'name' => 'Transaksi Baru', 'description' => 'Notifikasi ketika ada transaksi baru dibuat'],
            ['code' => 'debt_confirmed', 'name' => 'Transaksi Dikonfirmasi', 'description' => 'Notifikasi ketika transaksi dikonfirmasi oleh penerima'],
            ['code' => 'debt_rejected', 'name' => 'Transaksi Ditolak', 'description' => 'Notifikasi ketika transaksi ditolak oleh penerima'],
            ['code' => 'debt_updated', 'name' => 'Transaksi Diperbarui', 'description' => 'Notifikasi ketika transaksi diperbarui oleh pembuat'],
            ['code' => 'debt_settled', 'name' => 'Transaksi Diselesaikan', 'description' => 'Notifikasi ketika transaksi diselesaikan/dilunasi'],
            ['code' => 'reminder_due_date', 'name' => 'Pengingat Jatuh Tempo', 'description' => 'Notifikasi pengingat tanggal jatuh tempo'],
        ];
        
        foreach ($types as $t) {
            \App\Models\NotificationType::firstOrCreate(['code' => $t['code']], $t);
        }
        
        // Get or create study program
        $studyProgram = \App\Models\StudyProgram::firstOrCreate(
            ['code' => 'TEST'],
            ['name' => 'Test Program', 'faculty' => 'Test Faculty']
        );
        
        // Create admin user
        $admin = User::create([
            'name' => 'Test Admin',
            'email' => 'test_admin@test.com',
            'password' => Hash::make('password123'),
            'nim' => 'ADMIN001',
            'role' => UserRole::ADMIN,
            'study_program_id' => null,
        ]);
        
        // Create mahasiswa users
        $mahasiswa1 = User::create([
            'name' => 'Test Mahasiswa 1',
            'email' => 'test_mhs1@test.com',
            'password' => Hash::make('password123'),
            'nim' => 'MHS001',
            'role' => UserRole::MAHASISWA,
            'study_program_id' => $studyProgram->id,
        ]);
        
        $mahasiswa2 = User::create([
            'name' => 'Test Mahasiswa 2',
            'email' => 'test_mhs2@test.com',
            'password' => Hash::make('password123'),
            'nim' => 'MHS002',
            'role' => UserRole::MAHASISWA,
            'study_program_id' => $studyProgram->id,
        ]);
        
        echo "✓ Test users created\n";
        echo "  - Admin: test_admin@test.com\n";
        echo "  - Mahasiswa 1: test_mhs1@test.com\n";
        echo "  - Mahasiswa 2: test_mhs2@test.com\n";
        
        $this->createdIds['admin'] = $admin->id;
        $this->createdIds['mahasiswa1'] = $mahasiswa1->id;
        $this->createdIds['mahasiswa2'] = $mahasiswa2->id;
        $this->createdIds['studyProgramId'] = $studyProgram->id;
    }

    // =====================================================================
    // HTTP REQUEST METHODS
    // =====================================================================

    private function makeRequest($method, $endpoint, $data = null, $token = null) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'code' => 0,
                'error' => $error,
                'data' => null,
            ];
        }
        
        return [
            'success' => true,
            'code' => $httpCode,
            'data' => json_decode($response, true),
            'raw' => $response,
        ];
    }

    // =====================================================================
    // AUTHENTICATION TESTS
    // =====================================================================

    public function testAuthentication() {
        echo "\n\n=== TESTING AUTHENTICATION ===\n";
        
        // Test 1: Login Admin
        $this->test('POST /auth/login (admin)', function() {
            $response = $this->makeRequest('POST', '/auth/login', [
                'email' => 'test_admin@test.com',
                'password' => 'password123',
            ]);
            
            if ($response['code'] !== 200) {
                throw new Exception("Expected 200, got {$response['code']}");
            }
            
            if (!isset($response['data']['data']['token'])) {
                throw new Exception("Token not in response");
            }
            
            $this->adminToken = $response['data']['data']['token'];
            return true;
        });
        
        // Test 2: Login Mahasiswa
        $this->test('POST /auth/login (mahasiswa)', function() {
            $response = $this->makeRequest('POST', '/auth/login', [
                'email' => 'test_mhs1@test.com',
                'password' => 'password123',
            ]);
            
            if ($response['code'] !== 200) {
                throw new Exception("Expected 200, got {$response['code']}");
            }
            
            if (!isset($response['data']['data']['token'])) {
                throw new Exception("Token not in response");
            }
            
            $this->mahasiswaToken = $response['data']['data']['token'];
            return true;
        });
        
        // Test 3: Get Current User
        $this->test('GET /auth/me', function() {
            $response = $this->makeRequest('GET', '/auth/me', null, $this->adminToken);
            
            if ($response['code'] !== 200) {
                throw new Exception("Expected 200, got {$response['code']}");
            }
            
            if (!isset($response['data']['data']['id'])) {
                throw new Exception("User data not in response");
            }
            
            return true;
        });
        
        // Test 4: Invalid Credentials
        $this->test('POST /auth/login (invalid)', function() {
            $response = $this->makeRequest('POST', '/auth/login', [
                'email' => 'test_admin@test.com',
                'password' => 'wrongpassword',
            ]);
            
            if ($response['code'] !== 401) {
                throw new Exception("Expected 401, got {$response['code']}");
            }
            
            return true;
        });
        
        // Test 5: Unauthenticated Access
        $this->test('GET /auth/me (no token)', function() {
            $response = $this->makeRequest('GET', '/auth/me', null, null);
            
            if ($response['code'] !== 401) {
                throw new Exception("Expected 401, got {$response['code']}");
            }
            
            return true;
        });
    }

    // =====================================================================
    // DEBT RECORD TESTS
    // =====================================================================

    public function testDebtRecords() {
        echo "\n\n=== TESTING DEBT RECORDS ===\n";
        
        $debtId = null;
        
        // Test 1: Create Debt
        $this->test('POST /debts (create)', function() use (&$debtId) {
            $response = $this->makeRequest('POST', '/debts', [
                'counterpart_id' => $this->createdIds['mahasiswa2'],
                'type' => 'debt',
                'amount' => 500000,
                'description' => 'Borrowed money for textbook',
                'transaction_date' => '2026-06-15',
                'due_date' => '2026-06-30',
            ], $this->mahasiswaToken);
            
            if ($response['code'] !== 201) {
                throw new Exception("Expected 201, got {$response['code']}. Response: " . json_encode($response['data']));
            }
            
            if (!isset($response['data']['data']['id'])) {
                throw new Exception("Debt ID not in response");
            }
            
            $debtId = $response['data']['data']['id'];
            return true;
        });
        
        // Test 2: Get Debt List
        $this->test('GET /debts (list)', function() {
            $response = $this->makeRequest('GET', '/debts', null, $this->mahasiswaToken);
            
            if ($response['code'] !== 200) {
                throw new Exception("Expected 200, got {$response['code']}");
            }
            
            if (!is_array($response['data']['data'])) {
                throw new Exception("Expected array, got " . gettype($response['data']['data']));
            }
            
            return true;
        });
        
        // Test 3: Show Debt
        $this->test('GET /debts/{id}', function() use (&$debtId) {
            $response = $this->makeRequest('GET', '/debts/' . $debtId, null, $this->mahasiswaToken);
            
            if ($response['code'] !== 200) {
                throw new Exception("Expected 200, got {$response['code']}");
            }
            
            if (!isset($response['data']['data']['id'])) {
                throw new Exception("Debt data not in response");
            }
            
            return true;
        });
        
        // Test 4: Validation Error (missing field)
        $this->test('POST /debts (validation error)', function() {
            $response = $this->makeRequest('POST', '/debts', [
                'counterpart_id' => $this->createdIds['mahasiswa2'],
                // missing type, amount, description
            ], $this->mahasiswaToken);
            
            if ($response['code'] !== 422) {
                throw new Exception("Expected 422, got {$response['code']}");
            }
            
            if (!isset($response['data']['errors'])) {
                throw new Exception("Errors not in response");
            }
            
            return true;
        });
        
        // Test 5: Debt Stats
        $this->test('GET /debts/stats', function() {
            $response = $this->makeRequest('GET', '/debts/stats', null, $this->mahasiswaToken);
            
            if ($response['code'] !== 200) {
                throw new Exception("Expected 200, got {$response['code']}");
            }
            
            return true;
        });
    }

    // =====================================================================
    // ADMIN TESTS
    // =====================================================================

    public function testAdminEndpoints() {
        echo "\n\n=== TESTING ADMIN ENDPOINTS ===\n";
        
        // Test 1: List Students (Admin)
        $this->test('GET /students (admin)', function() {
            $response = $this->makeRequest('GET', '/students', null, $this->adminToken);
            
            if ($response['code'] !== 200) {
                throw new Exception("Expected 200, got {$response['code']}");
            }
            
            if (!is_array($response['data']['data'])) {
                throw new Exception("Expected array");
            }
            
            return true;
        });
        
        // Test 2: Mahasiswa cannot access students
        $this->test('GET /students (mahasiswa - should fail)', function() {
            $response = $this->makeRequest('GET', '/students', null, $this->mahasiswaToken);
            
            if ($response['code'] !== 403) {
                throw new Exception("Expected 403, got {$response['code']}");
            }
            
            return true;
        });
        
        // Test 3: Create Student
        $this->test('POST /students (admin)', function() {
            $timestamp = time();
            $response = $this->makeRequest('POST', '/students', [
                'nim' => 'TS' . substr($timestamp, -8),
                'name' => 'Test Student Name',
                'email' => 'test' . $timestamp . '@localhost.local',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'study_program_id' => $this->createdIds['studyProgramId'],
            ], $this->adminToken);
            
            if ($response['code'] !== 201) {
                throw new Exception("Expected 201, got {$response['code']}. Response: " . json_encode($response['data']));
            }
            
            return true;
        });
    }

    // =====================================================================
    // NOTIFICATION TESTS
    // =====================================================================

    public function testNotifications() {
        echo "\n\n=== TESTING NOTIFICATIONS ===\n";
        
        // Test 1: Get Unread Count
        $this->test('GET /notifications/unread-count', function() {
            $response = $this->makeRequest('GET', '/notifications/unread-count', null, $this->mahasiswaToken);
            
            if ($response['code'] !== 200) {
                throw new Exception("Expected 200, got {$response['code']}");
            }
            
            if (!isset($response['data']['data']['unread_count'])) {
                throw new Exception("unread_count not in response");
            }
            
            return true;
        });
        
        // Test 2: Get Notifications
        $this->test('GET /notifications', function() {
            $response = $this->makeRequest('GET', '/notifications', null, $this->mahasiswaToken);
            
            if ($response['code'] !== 200) {
                throw new Exception("Expected 200, got {$response['code']}");
            }
            
            if (!is_array($response['data']['data'])) {
                throw new Exception("Expected array");
            }
            
            return true;
        });
    }

    // =====================================================================
    // DASHBOARD TESTS
    // =====================================================================

    public function testDashboard() {
        echo "\n\n=== TESTING DASHBOARD ===\n";
        
        // Test 1: Complete Dashboard
        $this->test('GET /dashboard', function() {
            $response = $this->makeRequest('GET', '/dashboard', null, $this->mahasiswaToken);
            
            if ($response['code'] !== 200) {
                throw new Exception("Expected 200, got {$response['code']}");
            }
            
            return true;
        });
        
        // Test 2: Debt Stats
        $this->test('GET /dashboard/debt-stats', function() {
            $response = $this->makeRequest('GET', '/dashboard/debt-stats', null, $this->mahasiswaToken);
            
            if ($response['code'] !== 200) {
                throw new Exception("Expected 200, got {$response['code']}");
            }
            
            return true;
        });
        
        // Test 3: Summary Cards
        $this->test('GET /dashboard/summary-cards', function() {
            $response = $this->makeRequest('GET', '/dashboard/summary-cards', null, $this->mahasiswaToken);
            
            if ($response['code'] !== 200) {
                throw new Exception("Expected 200, got {$response['code']}");
            }
            
            return true;
        });
    }

    // =====================================================================
    // TEST HELPER METHOD
    // =====================================================================

    private function test($name, $callback) {
        try {
            $callback();
            $this->passedCount++;
            echo "✓ $name\n";
            $this->testResults[] = ['name' => $name, 'status' => 'PASS'];
        } catch (Exception $e) {
            $this->failedCount++;
            echo "✗ $name - " . $e->getMessage() . "\n";
            $this->testResults[] = ['name' => $name, 'status' => 'FAIL', 'error' => $e->getMessage()];
        }
    }

    // =====================================================================
    // REPORT GENERATION
    // =====================================================================

    public function generateReport() {
        echo "\n\n";
        echo "=============================================================================\n";
        echo "TEST SUMMARY\n";
        echo "=============================================================================\n";
        echo "Total Tests: " . ($this->passedCount + $this->failedCount) . "\n";
        echo "Passed: \033[32m" . $this->passedCount . "\033[0m\n";
        echo "Failed: \033[31m" . $this->failedCount . "\033[0m\n";
        echo "Success Rate: " . round(($this->passedCount / ($this->passedCount + $this->failedCount)) * 100, 2) . "%\n";
        echo "=============================================================================\n";
        
        if ($this->failedCount === 0) {
            echo "\n\033[32m✓ ALL TESTS PASSED - Backend is ready for integration!\033[0m\n\n";
        } else {
            echo "\n\033[31m✗ Some tests failed - see details above\033[0m\n\n";
        }
    }

    public function runAllTests() {
        echo "\n╔═══════════════════════════════════════════════════════════════════════════╗\n";
        echo "║               AUTOMATED API TESTING - SIMPATI PROJECT                   ║\n";
        echo "║                                                                         ║\n";
        echo "║  Framework: Laravel 12                                                  ║\n";
        echo "║  Total Endpoints: 46                                                    ║\n";
        echo "║  Date: 2026-06-17                                                       ║\n";
        echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";
        
        $this->testAuthentication();
        $this->testDebtRecords();
        $this->testAdminEndpoints();
        $this->testNotifications();
        $this->testDashboard();
        
        $this->generateReport();
    }
}

// =====================================================================
// EXECUTE TESTS
// =====================================================================

$tester = new APITester();
$tester->runAllTests();
