<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckUserStatus
{
    public function handle($request, Closure $next)
    {
        if (Auth::check() && Auth::user()->status == '1') {
            return $next($request);
        }

        // If user is not active, you can redirect them or return an error response
        return redirect()->route('inactive'); // Assuming you have a route named 'inactive'
    }
}