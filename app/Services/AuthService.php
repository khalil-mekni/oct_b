<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function login($email, $password)
    {
        $credentials = compact('email','password');
        if(!$token = auth()->attempt($credentials)) throw new \Exception("Invalid credentials");

        $user = auth()->user();
        $user->last_login_at = now();
        $user->save();

        return ['token' => $token, 'user' => $user];
    }

    public function logout() { auth()->logout(); return true; }

    public function me() { return auth()->user(); }
}