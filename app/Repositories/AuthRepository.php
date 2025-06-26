<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthRepository
{
    public function createAccount(array $data): ?User
    {
        try {
            return User::create([
                'name' => $data['name'],
                'username' => $data['userName'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);
        } catch (\Exception $e) {
            Log::error("CreateAccount error: " . $e->getMessage());
            return null;
        }
    }

}
