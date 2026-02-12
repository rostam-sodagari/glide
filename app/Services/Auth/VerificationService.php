<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VerificationService
{
    public function __construct(
        private RateLimitService $rateLimiter,
        private Request $request
    ) {}

    /**
    * Verify the user's email address.
    *
    * @param  mixed  $userOrId  The user instance or user ID to verify
    * @param  string|null  $hash  The hash to validate the verification request (optional if user instance is provided)
    * @return User The verified user instance
    * @throws ValidationException If the verification fails due to invalid token or already verified email
    */
    public function verify(mixed $userOrId = null, ?string $hash = null): User
    {
        $this->rateLimiter->throttle('verify-email', $this->request);

        $user = $userOrId ? $this->getVerifyingUser($userOrId, $hash) : $this->request->user();

        if (! $user || $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => __('verification.already_verified'),
            ]);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return $user;
    }

    /**
    * Resend the email verification notification to the user.
    *
    * @param  string  $email  The email address of the user to resend the verification email to
    * @return void
    */
    public function resendVerification(string $email): void
    {
        $this->rateLimiter->throttle('resend-verification', $this->request);

        $user = User::where('email', strtolower(trim($email)))->first();
        if (! $user || $user->hasVerifiedEmail()) {
            return;  // Silent - anti-enumeration
        }

        $user->sendEmailVerificationNotification();
    }

    
    private function getVerifyingUser(mixed $id, ?string $hash): User
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $user->getKey(), (string) $id)) {
            throw ValidationException::withMessages(['token' => __('verification.invalid_signature')]);
        }

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            throw ValidationException::withMessages(['token' => __('verification.invalid_signature')]);
        }

        return $user;
    }
}
