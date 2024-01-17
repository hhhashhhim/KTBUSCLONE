<?php

namespace App\Http\Middleware;

use Closure;
use Laravel\Sanctum\Sanctum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomSanctumTokenVerify
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Get the token from the hidden field
        $token = $request->input('token');

        if (!$token) {
            return response()->json(['message' => 'Token not provided'], 401);
        }

        // Retrieve the Sanctum token model
        $tokenModel = Sanctum::personalAccessTokenModel();

        // Find the token by token value
        $accessToken = $tokenModel::findToken($token);

        if ($accessToken) {
            // Token is valid, retrieve the user
            $user = $accessToken->tokenable;
            if ($user) {
                // Manually login the user using Auth facade
                Auth::login($user);

                return $next($request);
                
            } else {
                // Token is valid, but unable to retrieve the user
                return response()->json(['message' => 'Unable to retrieve user from token'], 500);
            }
        } else {
            // Token is invalid or expired
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    }
}
