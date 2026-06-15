<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
    ) {}

    /**
     * Login user and return access token
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                $request->getEmail(),
                $request->getPassword()
            );

            return response()->json([
                'message' => 'Login berhasil',
                'data' => [
                    'user' => new UserResource($result['user']),
                    'token' => $result['token'],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Login gagal',
                'error' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Get current authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request->user());

        return response()->json([
            'message' => 'User data retrieved',
            'data' => new UserResource($user),
        ], 200);
    }

    /**
     * Logout user and revoke all tokens
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Logout berhasil',
        ], 200);
    }

    /**
     * Refresh access token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $token = $this->authService->refreshToken($request->user());

            return response()->json([
                'message' => 'Token refreshed successfully',
                'data' => [
                    'token' => $token,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Refresh token failed',
                'error' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Update FCM token for push notifications
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fcm_token' => 'required|string',
            'device_name' => 'nullable|string|max:255',
        ]);

        $this->authService->updateFcmToken(
            $request->user(),
            $validated['fcm_token'],
            $validated['device_name'] ?? null
        );

        return response()->json([
            'message' => 'FCM token updated successfully',
        ], 200);
    }

    /**
     * Change user password
     *
     * @param ChangePasswordRequest $request
     * @return JsonResponse
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->changePassword(
                $request->user(),
                $request->getOldPassword(),
                $request->getNewPassword()
            );

            return response()->json([
                'message' => 'Password changed successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Change password failed',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Verify if email exists
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $exists = $this->authService->emailExists($request->input('email'));

        return response()->json([
            'email' => $request->input('email'),
            'exists' => $exists,
        ], 200);
    }

    /**
     * Verify if NIM exists
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkNim(Request $request): JsonResponse
    {
        $request->validate([
            'nim' => 'required|string',
        ]);

        $exists = $this->authService->nimExists($request->input('nim'));

        return response()->json([
            'nim' => $request->input('nim'),
            'exists' => $exists,
        ], 200);
    }
}
