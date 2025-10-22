<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Message;

class DailyMessageLimit
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $limit = (int) ($user->role?->daily_msg_limit ?? 0);
        if ($limit === 0) {
            return $next($request);
        }

        $today = Carbon::now(config('app.timezone'))->toDateString();

        $count = Message::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->count();

        if ($count >= $limit) {
            return response()->json([
                'message' => 'Daily message limit exceeded',
                'limit'   => $limit,
                'date'    => $today,
            ], 429);
        }

        return $next($request);
    }
}
