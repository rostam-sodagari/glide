<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\Auth\ResetPasswordRequest;
use App\Services\Auth\PasswordResetService;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;

class PasswordResetController extends BaseController
{
    public function __construct(public PasswordResetService $passwordResetService) {}

    /**
     * Handle a password reset link request.
     *
     * @param  ForgotPasswordRequest  $request  The validated request containing the email address
     * @return JsonResponse The JSON response indicating the result of the password reset link request
     */
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $result = $this->passwordResetService->sendResetLink($request->validated());

            return $this->success($result['data'], 200, [], $result['message']);
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        }
    }

    /**
     * Handle a password reset request.
     *
     * @param  ResetPasswordRequest  $request  The validated request containing email, token, new password, and password confirmation
     * @return JsonResponse The JSON response indicating the result of the password reset process
     *
     * @throws ValidationException If the reset token is invalid, the user is not found, or if there are too many attempts
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $result = $this->passwordResetService->resetPassword($request->validated());

            return $this->success($result['data'], 200, [], $result['message']);
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        }
    }
}
