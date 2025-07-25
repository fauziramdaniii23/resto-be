<?php

namespace App\Http\Controllers;

use App\Helper\ApiResponse;
use App\Http\Requests\RegisterRequest;
use App\Mail\ForgotPasswordMail;
use App\Mail\VerifyEmail;
use App\Models\User;
use App\Notifications\CustomVerifyEmail;
use App\Repositories\AuthRepository;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

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

            $linkVerifyUrl = config('app.frontend_url') . '/email-verification?token=' . $token;
            Mail::to($user->email)->send(new VerifyEmail($linkVerifyUrl));

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

            $linkVerifyUrl = config('app.frontend_url') . '/email-verification?token=' . $token;
            Mail::to($user->email)->send(new VerifyEmail($linkVerifyUrl));

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

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return ApiResponse::ErrorResponse('', 'User not found');
        }

        $token = Str::random(60);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token),
                'created_at' => Carbon::now()
            ]
        );

        $resetUrl = config('app.frontend_url') . '/reset-password?token=' . urlencode($token) . '&email=' . urlencode($request->email);

        Mail::to($request->email)->send(new ForgotPasswordMail($resetUrl));
        $message = 'Reset token sent to your email';

        return ApiResponse::BaseResponse($message, $message);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|min:6|confirmed',
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return ApiResponse::ErrorResponse('', 'User not found');
        }

        $reset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!Hash::check($request->token, $reset->token)) {
            return ApiResponse::ErrorResponse('', 'Invalid token');
        }

        if (Carbon::parse($reset->created_at)->addMinutes(60)->isPast()) {
            return ApiResponse::ErrorResponse('', 'Token has expired');
        }

        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        $message = 'Password has been reset successfully';
        return ApiResponse::BaseResponse($message, $message);
    }
}
