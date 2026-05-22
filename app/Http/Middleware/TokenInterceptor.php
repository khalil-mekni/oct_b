<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TokenInterceptor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the request has a valid Passport token via the 'api' guard
        if (!Auth::guard('api')->check()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'A valid token is required to access this resource.',
                'status' => 401
            ], 401);
        }

        return $next($request);
    }
}
