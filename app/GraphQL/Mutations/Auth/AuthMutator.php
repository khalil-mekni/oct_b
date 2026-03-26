<?php

namespace App\GraphQL\Mutations\Auth;

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
            throw new \Exception('Invalid credentials.');
        }

        if (! $user->hasVerifiedEmail()) {
            throw new \Exception('Email not verified. Please verify your email first.');
        }

        $token = $user->createToken('API Token')->accessToken;

        return [
            'token' => $token,
            'user' => $user,
        ];
    }


    public function forgotPassword($_, array $args)
{
    $validator = Validator::make($args, [
        'email' => ['required', 'email'],
    ], [
        'email.required' => 'L’email est obligatoire.',
        'email.email' => 'Le format de l’email est invalide.',
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

    return 'Un lien de réinitialisation a été envoyé à votre adresse email.';
}

public function resetPassword($_, array $args)
{
    $validator = Validator::make($args, [
        'email' => ['required', 'email'],
        'token' => ['required', 'string'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ], [
        'email.required' => 'L’email est obligatoire.',
        'email.email' => 'Le format de l’email est invalide.',
        'token.required' => 'Le token est obligatoire.',
        'password.required' => 'Le mot de passe est obligatoire.',
        'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
    ]);

    if ($validator->fails()) {
        throw new \Exception($validator->errors()->first());
    }

    $status = Password::reset(
        [
            'email' => $args['email'],
            'token' => $args['token'],
            'password' => $args['password'],
            'password_confirmation' => $args['password_confirmation'],
        ],
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

    return 'Votre mot de passe a été réinitialisé avec succès.';
}

    public function register($_, array $args)
    {
        $validator = Validator::make($args, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L’email est obligatoire.',
            'email.email' => 'Le format de l’email est invalide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        $user = User::create([
            'name' => $args['name'],
            'email' => $args['email'],
            'password' => Hash::make($args['password']),
            'role' => 'USER',
        ]);

        // Envoi email vérification
        $user->sendEmailVerificationNotification();

        $token = $user->createToken('API Token')->accessToken;

        return [
            'token' => $token,
            'user' => $user,
        ];
    }

    public function verifyEmail($_, array $args)
    {
        try {
            $id = base64_decode($args['token'], true);

            if ($id === false || ! is_numeric($id)) {
                throw new \Exception('Invalid verification token.');
            }

            $user = User::findOrFail((int) $id);

            if ($user->hasVerifiedEmail()) {
                return 'Email already verified.';
            }

            $user->email_verified_at = now();
            $user->save();

            return 'Email verified successfully.';
        } catch (\Exception $e) {
            throw new \Exception('Invalid or expired verification token.');
        }
    }

    public function resendVerificationEmail()
    {
        $user = Auth::user();

        if (! $user) {
            throw new \Exception('Unauthenticated.');
        }

        if ($user->hasVerifiedEmail()) {
            return 'Email already verified.';
        }

        $user->sendEmailVerificationNotification();

        return 'Verification email sent successfully.';
    }

    public function logout()
    {
        $user = Auth::user();

        if (! $user) {
            throw new \Exception('Unauthenticated.');
        }

        // ✅ SOLUTION CORRECTE
        $user->tokens()->delete();

        return true;
    }
}