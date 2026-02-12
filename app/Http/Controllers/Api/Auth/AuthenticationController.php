<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Services\Auth\AuthService;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;

class AuthenticationController extends BaseController
{
    public function __construct(public AuthService $authService) {}

    /**
     * Authenticate a user and return a JSON response.
     *
     * @param  LoginRequest  $request  The login request containing user credentials
     * @return JsonResponse The JSON response containing authentication result
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            event(new Authenticated('api', Auth::user()));

            return $this->success($result);
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        }
    }

    /**
     * Register a new user
     *
     * @param  RegisterRequest  $request  The validated registration request containing user details
     * @return JsonResponse The JSON response containing the registration result
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());

            event(new Registered($request->user()));

            return $this->success($result, 201, ['message' => __('auth.registered_verify')]);
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        }
    }
}
