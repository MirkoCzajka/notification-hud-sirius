<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AdminUser
{
    public function handle($request, Closure $next)
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = $user->fresh(['role']);
        if (($user->role->type ?? null) !== 'admin') {
            return response()->json([
                'message' => 'You do not have permissions to access this endpoint.',
            ], 403);
        }

        return $next($request);
    }
}
