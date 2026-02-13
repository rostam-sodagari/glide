<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ValidateApiSignature
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->hasValidSignature()) {
            throw ValidationException::withMessages([
                'signature' => ['Invalid or expired verification link.'],
            ]);
        }

        return $next($request);
    }
}
