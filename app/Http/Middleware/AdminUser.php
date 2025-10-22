<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $isAdmin = ($user->role?->type ?? null) === 'admin';
        if (!$isAdmin) {
            return response()->json([
                'message' => 'You do not have permissions to access this endpoint.'
            ], 403);
        }

        return $next($request);
    }
}
