<?php

namespace App\GraphQL\Mutations\Auth;

use GraphQL\Error\UserError;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class AuthMutator
{
    public function login($_, array $args)
    {
        $validator = Validator::make($args, [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $user = User::where('email', $args['email'])->first();

        if (! $user || ! Hash::check($args['password'], $user->password)) {
    throw new UserError('Invalid credentials.');
}

if (! $user->hasVerifiedEmail()) {
    throw new UserError('Email not verified.');
}

if (! $user->is_active) {
    throw new UserError('Account inactive.');
}

if ($user->role === 'PENDING') {
    throw new UserError('Account pending admin approval.');
}

        $user->last_login_at = now();
        $user->save();

        $token = $user->createToken('auth_token')->accessToken;

        return [
            'token' => $token,
            'user' => $user,
        ];
    }

    public function register($_, array $args)
    {
        $validator = Validator::make($args, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        $user = User::create([
            'name' => $args['first_name'] . ' ' . $args['last_name'],
            'first_name' => $args['first_name'],
            'last_name' => $args['last_name'],
            'email' => $args['email'],
            'password' => Hash::make($args['password']),
            'role' => 'PENDING',
            'phone' => $args['phone'] ?? null,
            'birth_date' => $args['birth_date'] ?? null,
            'address' => $args['address'] ?? null,
            'is_active' => true,
            
        ]);

        $user->sendEmailVerificationNotification();

        $token = $user->createToken('auth_token')->accessToken;

        return [
            'token' => $token,
            'user' => $user,
        ];
    }

    public function me()
    {
        return Auth::guard('api')->user();
    }

    public function logout()
    {
        $user = Auth::guard('api')->user();

        if (! $user) {
            throw new \Exception('Unauthenticated.');
        }

        $user->tokens->each(function ($token) {
            $token->delete();
        });

        return true;
    }

    public function resendVerificationEmail()
    {
        $user = Auth::guard('api')->user();

        if (! $user) {
            throw new \Exception('Unauthenticated.');
        }

        if ($user->hasVerifiedEmail()) {
            return 'Email already verified.';
        }

        $user->sendEmailVerificationNotification();

        return 'Verification email sent.';
    }

    public function verifyEmail($_, array $args)
    {
        $id = base64_decode($args['token'], true);

        if (! $id) {
            throw new \Exception('Invalid token.');
        }

        $user = User::findOrFail((int) $id);

        if ($user->hasVerifiedEmail()) {
            return 'Already verified.';
        }

        $user->email_verified_at = now();
        $user->save();

        return 'Email verified.';
    }

    public function forgotPassword($_, array $args)
    {
        $validator = Validator::make($args, [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        $status = Password::sendResetLink([
            'email' => $args['email'],
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw new \Exception(__($status));
        }

        return 'Reset link sent.';
    }

    public function resetPassword($_, array $args)
    {
        $validator = Validator::make($args, [
            'email' => ['required', 'email'],
            'token' => ['required'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        $status = Password::reset(
            $args,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new \Exception(__($status));
        }

        return 'Password reset successfully.';
    }
}