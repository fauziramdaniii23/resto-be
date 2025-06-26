<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $tokenExpiration = env('PASSPORT_TOKEN_EXPIRE', 3600);
        Passport::tokensExpireIn(now()->addSeconds($tokenExpiration));
        Passport::refreshTokensExpireIn(now()->addSeconds($tokenExpiration));
        Passport::personalAccessTokensExpireIn(now()->addSeconds($tokenExpiration));
    }
}
