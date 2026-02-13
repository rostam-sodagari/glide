<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetService
{
    public function __construct(public RateLimitService $rateLimiter, public Request $request) {}

    /**
     * Send a password reset link to the given email.
     *
     * @param  array  $data  The data containing the email address to send the reset link to
     * @return array The result of the password reset link sending process, typically containing a success message and the email address
     */
    public function sendResetLink(array $data): array
    {
        $this->rateLimiter->throttle('forgot-password', $this->request);

        Password::sendResetLink(['email' => $data['email']]);

        return [
            'message' => __('passwords.sent'),
            'data' => ['email' => $data['email']],
        ];
    }

    /**
     * Reset the password using the provided token and new password.
     *
     * @param  array  $data  The data containing email, token, new password, and password confirmation
     * @return array The result of the password reset process, typically containing a success message and the email address
     *
     * @throws ValidationException If the reset token is invalid, the user is not found, or if there are too many attempts
     */
    public function resetPassword(array $data): array
    {
        $this->rateLimiter->throttle('reset-password', $this->request);

        $status = Password::reset(
            [
                'email' => $data['email'],
                'token' => $data['token'],
                'password' => $data['password'],
                'password_confirmation' => $data['password_confirmation'],
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            $errors = match ($status) {
                Password::INVALID_USER => ['email' => __('passwords.user')],
                Password::INVALID_TOKEN => ['token' => __('passwords.token')],
                default => ['email' => __('passwords.throttled')],
            };

            throw ValidationException::withMessages($errors);
        }

        return [
            'message' => __('passwords.reset'),
            'data' => ['email' => $data['email']],
        ];
    }
}
