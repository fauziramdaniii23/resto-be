<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();

            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'username' => $this->generateUniqueUsername($googleUser->getName()),
                    'email' => $googleUser->getEmail(),
                    'password' => bcrypt(Str::random(16)),
                    'email_verified_at' => now(),
                ]);
            }

            $tokenResult = $user->createToken('GoogleToken');

            $frontendUrl = config('app.frontend_url');
            return redirect()->away("{$frontendUrl}/login-google?token={$tokenResult->accessToken}");

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Gagal login dengan Google',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    private function generateUniqueUsername($name): string
    {
        $base = Str::slug($name, '');
        $username = $base;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }

}
