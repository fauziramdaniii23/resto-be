<?php

namespace App\Http\Controllers;

use App\Helper\ApiResponse;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Notifications\CustomVerifyEmail;
use App\Repositories\AuthRepository;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class AuthController extends Controller
{
    protected AuthRepository $authRepository;

    public function __construct(AuthRepository $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $existingUser = User::where('email', $request->email)->first();

            if ($existingUser) {
                if (!$existingUser->hasVerifiedEmail()) {
                    throw new \Exception('Email belum diverifikasi.');
                } else {
                    throw new \Exception('Email sudah terdaftar dan diverifikasi.');
                }
            }

            $user = $this->authRepository->createAccount($request->validated());
            if (!$user) {
                throw new \Exception('Terjadi kesalahan saat membuat akun.');
            }

            $credentials = $request->only('email', 'password');
            Auth::attempt($credentials);

            $token = Crypt::encrypt($user->id);

            $user->notify(new CustomVerifyEmail($token));

            return ApiResponse::BaseResponse(
                [
                    'user' => $user,
                    'token' => $token
                ]
            );
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return ApiResponse::ErrorResponse($message, $message);
        }
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            $credentials = [
                'email' => $validated['email'],
                'password' => $validated['password']
            ];

            if (!Auth::attempt($credentials)) {
                throw new \Exception('Email atau password salah');
            }

            $user = Auth::user();

            if (is_null($user->email_verified_at)) {
                throw new \Exception('Email belum diverifikasi.');
            }

            $token = $user->createToken('appToken')->accessToken;

            return ApiResponse::BaseResponse([
                'user' => $user,
                'token' => $token
            ]);

        } catch (\Exception $e) {
            $message = $e->getMessage();
            return ApiResponse::ErrorResponse($message, $message);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                throw new \Exception('Unauthenticated.');
            }

            $request->user()->token()->revoke();
            return ApiResponse::BaseResponse(null, 'Logout berhasil');
        } catch (\Exception $e) {
            return ApiResponse::ErrorResponse($e->getMessage(), 'Terjadi kesalahan saat logout');
        }
    }

    public function emailVerified(Request $request): JsonResponse
    {
        try {
            $token = $request->input('token');

            $userId = Crypt::decrypt($token);

            $user = User::findOrFail($userId);

            if ($user->hasVerifiedEmail()) {
                return ApiResponse::BaseResponse(null, 'Email sudah diverifikasi, Silakan login');
            }

            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }

            return ApiResponse::BaseResponse(null, 'Email berhasil diverifikasi, Silakan login');
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return ApiResponse::ErrorResponse($message, $message);
        }

    }

    public function emailVerificationNotification(Request $request): JsonResponse
    {
        try {
            $credentials = $request->only('email', 'password');

            if (!Auth::attempt($credentials)) {
                throw new \Exception('Email atau password salah');
            }

            $user = Auth::user();

            if (!$user) {
                throw new \Exception('Pengguna tidak ditemukan.');
            }

            $token = Crypt::encrypt($user->id);

            $user->notify(new CustomVerifyEmail($token));

            return ApiResponse::BaseResponse(
                [
                    'user' => $user,
                    'token' => $token
                ]
            );
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return ApiResponse::ErrorResponse($message, $message);
        }
    }
    public function user(): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                throw new \Exception('Unauthenticated.');
            }
            return ApiResponse::BaseResponse($user);
        } catch (\Exception $e) {
            return ApiResponse::ErrorResponse($e->getMessage(), 'Terjadi kesalahan saat mengambil data pengguna');
        }
    }
}
