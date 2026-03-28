<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function login($email, $password)
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw new \Exception("Invalid credentials");
        }

        if (!$user->hasVerifiedEmail()) {
            throw new \Exception("Email not verified");
        }

        if (!$user->is_active) {
            throw new \Exception("Account is inactive");
        }

        $token = $user->createToken('auth_token')->accessToken;

        $user->last_login_at = now();
        $user->save();

        return [
            'token' => $token,
            'user' => $user,
        ];
    }

    public function logout()
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            throw new \Exception("Unauthenticated");
        }

        $user->tokens->each(function ($token) {
            $token->delete();
        });

        return true;
    }

    public function me()
    {
        return Auth::guard('api')->user();
    }
}