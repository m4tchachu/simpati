<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Login user with email and password
     *
     * @param string $email
     * @param string $password
     * @return array{user: User, token: string}
     * @throws ValidationException
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        if (isset($user->is_active) && !$user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Akun Anda non-aktif, silahkan hubungi admin',
            ]);
        }

        $token = $user->createToken(
            name: 'auth_token',
            expiresAt: now()->addDays(7)
        )->plainTextToken;

        // Log successful login
        $this->logAction($user, 'login');

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Logout user and invalidate all tokens
     *
     * @param User $user
     * @return bool
     */
    public function logout(User $user): bool
    {
        // Delete all access tokens
        $user->tokens()->delete();

        // Log logout action
        $this->logAction($user, 'logout');

        return true;
    }

    /**
     * Register new student user
     *
     * @param array{
     *     nim: string,
     *     name: string,
     *     email: string,
     *     password: string,
     *     study_program_id: int
     * } $data
     * @return User
     */
    public function registerStudent(array $data): User
    {
        $user = User::create([
            'nim' => strtoupper($data['nim']),
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'role' => UserRole::MAHASISWA,
            'study_program_id' => $data['study_program_id'],
        ]);

        // Log registration
        $this->logAction($user, 'register');

        return $user;
    }

    /**
     * Create admin user (by super admin)
     *
     * @param array{
     *     name: string,
     *     email: string,
     *     password: string
     * } $data
     * @param User $createdBy
     * @return User
     */
    public function createAdmin(array $data, User $createdBy): User
    {
        $admin = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'role' => UserRole::ADMIN,
        ]);

        // Log admin creation
        $this->logAction($createdBy, 'create_admin');

        return $admin;
    }

    /**
     * Refresh access token
     *
     * @param User $user
     * @return string
     */
    public function refreshToken(User $user): string
    {
        // Revoke current token via the tokens relationship
        $currentToken = $user->currentAccessToken();
        if ($currentToken !== null) {
            // Delete the specific token
            $user->tokens()->where('id', $currentToken->id)->delete();
        }

        // Create new token
        $token = $user->createToken(
            name: 'auth_token',
            expiresAt: now()->addDays(7)
        )->plainTextToken;

        return $token;
    }

    /**
     * Validate if email exists
     *
     * @param string $email
     * @return bool
     */
    public function emailExists(string $email): bool
    {
        return User::where('email', strtolower($email))->exists();
    }

    /**
     * Validate if NIM exists (for students)
     *
     * @param string $nim
     * @return bool
     */
    public function nimExists(string $nim): bool
    {
        return User::where('nim', strtoupper($nim))->exists();
    }

    /**
     * Get current user info
     *
     * @param User $user
     * @return User
     */
    public function getCurrentUser(User $user): User
    {
        return $user->load('studyProgram', 'fcmTokens');
    }

    /**
     * Update user FCM token
     *
     * @param User $user
     * @param string $fcmToken
     * @param string|null $deviceName
     * @return void
     */
    public function updateFcmToken(User $user, string $fcmToken, ?string $deviceName = null): void
    {
        // Check if token already exists
        $existingToken = $user->fcmTokens()->where('token', $fcmToken)->first();

        if ($existingToken) {
            $existingToken->update([
                'device_name' => $deviceName,
                'is_active' => true,
            ]);
        } else {
            $user->fcmTokens()->create([
                'token' => $fcmToken,
                'device_name' => $deviceName,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Change user password
     *
     * @param User $user
     * @param string $currentPassword
     * @param string $newPassword
     * @return bool
     * @throws ValidationException
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password saat ini tidak sesuai.',
            ]);
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        // Invalidate all other tokens for security
        $currentToken = $user->currentAccessToken();
        $user->tokens()->where('id', '!=', $currentToken->id)->delete();

        // Log password change
        $this->logAction($user, 'change_password');

        return true;
    }

    /**
     * Log authentication action
     *
     * @param User $user
     * @param string $action
     * @return void
     */
    private function logAction(User $user, string $action): void
    {
        $user->auditLogs()->create([
            'action' => $action,
            'table_name' => 'users',
            'record_id' => $user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
