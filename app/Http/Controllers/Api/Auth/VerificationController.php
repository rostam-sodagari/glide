<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\Auth\ResendRequest;
use App\Services\Auth\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class VerificationController extends BaseController
{
    public function __construct(public VerificationService $verificationService) {}

    /**
     * Verify user's email using the provided ID and hash.
     *
     * @param  string  $id  The ID of the user to verify
     * @param  string  $hash  The hash to validate the verification request
     * @return JsonResponse JSON response indicating success or failure of verification
     */
    public function verify(string $id, string $hash): JsonResponse
    {
        try {
            $this->verificationService->verify($id, $hash);

            return $this->success(['message' => __('verification.verified_success')]);
        } catch (ValidationException $e) {

            return $this->validationError($e->errors());
        }
    }

    /**
     * Resend verification email to the user.
     *
     * @param  ResendRequest  $request  The resend verification request containing user email
     * @return JsonResponse JSON response indicating success or failure of resend operation
     */
    public function resend(ResendRequest $request): JsonResponse
    {
        $this->verificationService->resendVerification($request->email);

        return $this->success(['message' => __('verification.resend_success')]);
    }
}
