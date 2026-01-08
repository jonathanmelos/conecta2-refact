<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class ApiTokenMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-API-KEY') ?? $request->query('api_key');

        if (!$token) {
            return response()->json(['message' => 'API key missing.'], 401);
        }

        $hash = hash('sha256', $token);
        $user = User::where('api_token_hash', $hash)->first();

        if (!$user) {
            return response()->json(['message' => 'API key invalid.'], 401);
        }

        $request->attributes->set('api_user', $user);

        return $next($request);
    }
}
