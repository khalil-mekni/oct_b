<?php
namespace App\GraphQL\Resolvers;

use Illuminate\Support\Facades\Auth;

class UserResolver
{
    public function me()
    {
        return Auth::user();
    }
}