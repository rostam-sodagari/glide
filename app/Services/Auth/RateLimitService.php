<?php

namespace App\Services\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class RateLimitService
{
    /**
     * Throttle a specific action for the current request.
     *
     * @param  string  $action  The action identifier to throttle
     * @param  Request  $request  The HTTP request instance
     */
    public function throttle(string $action, Request $request): void
    {
        $key = $this->getThrottleKey($action, $request);
        $maxAttempts = config("auth.api.rate_limit_attempts.{$action}", config('auth.api.rate_limit_attempts', 5));
        $decaySeconds = config("auth.api.rate_limit_decay.{$action}", config('auth.api.rate_limit_decay', 60));

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => trans('auth.throttled', ['seconds' => $seconds]),
            ]);
        }

        RateLimiter::hit($key, $decaySeconds);
    }

    protected function getThrottleKey(string $action, Request $request): string
    {
        return 'auth:'.$action.':'.sha1($request->email.$request->ip());

    }
}
