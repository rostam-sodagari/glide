<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    public function __construct(public RateLimitService $rateLimiter, public Request $request) {}

    /**
     * Register a new user with the provided data.
     *
     * @param  array  $data  The user registration data containing fields such as name, email, password, etc.
     * @return array The result of the registration process, typically containing always a success message.
     */
    public function register(array $data): array
    {
        $this->rateLimiter->throttle('register', $this->request);

        $data['email'] = strtolower(trim($data['email']));
        $data['password'] = $this->securePassword($data['password']);
        unset($data['device_name']);

        // Check if user already exists to prevent email enumeration
        $exists = User::where('email', $data['email'])->exists();

        if (! $exists) {
            $user = User::create($data);
            event(new Registered($user));
            $user->sendEmailVerificationNotification();
        }

        // Always return success message to prevent email enumeration
        return [
            'message' => __('verification.verification_sent'),
        ];
    }

    /**
     * Authenticate a user with the provided credentials
     *
     * @param  array  $credentials  An associative array containing user authentication credentials
     *                              (typically 'email' and 'password' keys)
     * @return array An array containing authentication result data
     *               (typically includes user information and/or authentication token)
     */
    public function login(array $credentials): array
    {
        $this->rateLimiter->throttle('login', $this->request);

        // Remove device_name as it's not a credential
        unset($credentials['device_name']);

        if (! Auth::attempt($credentials, $this->request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $user = Auth::user();
        if (! $user instanceof User) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        event(new Authenticated('api', $user));

        if (! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages(['email_verification' => __('verification.not_verified')]);
        }

        $this->revokeExcessTokens($user);

        $token = $this->issueToken(
            $user,
            $this->request->device_name ?? 'api',
            ['*'],
            $this->request->boolean('remember') ? null : now()->addDays(config('auth.api.token_expiry_days'))
        );

        return ['user' => $user->makeVisible(['email']), 'token' => $token];
    }

    /**
     * Secures a password by hashing it using a secure hashing algorithm.
     *
     * @param  string  $password  The plain text password to be secured
     * @return string The hashed password
     */
    private function securePassword(string $password): string
    {
        // Laravel built-in uncompromised check
        if (config('auth.api.password_uncompromised_check', true)) {
            $rule = \Illuminate\Validation\Rules\Password::defaults()
                ->uncompromised(config('auth.api.uncompromised_threshold', 1));
            $validator = Validator::make(['password' => $password], ['password' => $rule]);
            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'password' => $validator->errors()->first('password') ?: __('auth.weak_password'),
                ]);
            }
        }

        return Hash::needsRehash($password) ? Hash::make($password) : $password;
    }

    /**
     * Issue an authentication token for a user.
     *
     * @param  User  $user  The user for whom to issue the token.
     * @param  string  $name  The name or identifier for the token.
     * @param  array  $abilities  The abilities or permissions granted to the token. Defaults to ['*'] for all abilities.
     * @param  mixed  $expiresAt  Optional expiration time for the token. If null, the token does not expire.
     * @return string The issued authentication token.
     */
    private function issueToken(User $user, string $name, array $abilities = ['*'], $expiresAt = null): string
    {
        return $user->createToken($name, $abilities, $expiresAt)->plainTextToken;
    }

    /**
     * Revoke excess tokens for a user.
     *
     * Removes any tokens that exceed the maximum allowed number of active tokens
     * for the given user account.
     *
     * @param  User  $user  The user whose excess tokens should be revoked.
     */
    private function revokeExcessTokens(User $user): void
    {
        PersonalAccessToken::where('tokenable_id', $user->id)
            ->latest('created_at')
            ->skip(config('auth.api.max_device_tokens'))
            ->delete();
    }
}
