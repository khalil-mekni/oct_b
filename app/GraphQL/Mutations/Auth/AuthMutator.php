<?php

namespace App\GraphQL\Mutations\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Notifications\VerifyEmailGraphQL;

class AuthMutator
{

 
   public function login($_, array $args)
{
    $user = User::where('email', $args['email'])->first();

    if (!$user || !Hash::check($args['password'], $user->password)) {
        throw new \Exception('Invalid credentials');
    }

    if (!$user->hasVerifiedEmail()) {
        throw new \Exception('Email not verified. Please verify your email first.');
    }

    $token = $user->createToken('API Token')->accessToken;

    return [
        'token' => $token,
        'user' => $user
    ];
}

    public function register($_, array $args)
{
    $user = User::create([
        'name' => $args['name'],
        'email' => $args['email'],
        'password' => bcrypt($args['password']),
        'role' => $args['role'] ?? 'ADMIN',
    ]);

    // Envoie l'email de vérification
    $user->sendEmailVerificationNotification();

    $token = $user->createToken('API Token')->accessToken;

    return [
        'token' => $token,
        'user' => $user,
    ];
}
public function verifyEmail($_, array $args)
{
    $id = base64_decode($args['token']);
    $user = User::findOrFail($id);

    if ($user->email_verified_at) {
        return "Email already verified";
    }

    $user->email_verified_at = now();
    $user->save();

    return "Email verified successfully";
}
public function resendVerificationEmail()
{
    $user = auth()->user();

    if ($user->hasVerifiedEmail()) {
        return "Email already verified.";
    }

    $user->sendEmailVerificationNotification();

    return "Verification email sent!";
}


    public function logout()
    {
        auth()->user()->token()->revoke();

        return true;
    }
}