<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return ApiResponse::error('Unauthorized', 401);
        }

        if ($user->role !== 'admin') {
            return ApiResponse::error('Forbidden: admin only', 403);
        }

        return $next($request);
    }
}
