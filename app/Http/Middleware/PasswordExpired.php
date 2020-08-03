<?php

namespace App\Http\Middleware;

use Closure;

class PasswordExpired
{
    public function handle($request, Closure $next)
    {
        if ($request->user()->daysSincePasswordChange() > config('settings.password_ttl')) {
            $request->session()->put('password_expired', true);

            return redirect()->route('account.edit');
        }

        return $next($request);
    }
}
