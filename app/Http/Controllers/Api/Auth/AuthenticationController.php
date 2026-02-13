<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Services\Auth\AuthService;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Http\Request;
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
            return $this->success([], 201, [], $result['message']);
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        }
    }


    /**
     * Log out the authenticated user.
     *
     * Invalidates the user's current session and returns a JSON response.
     *
     * @param Request $request The incoming HTTP request
     * @return JsonResponse The JSON response indicating logout status
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            $token = $user->currentAccessToken();
            if ($token) {
                $token->delete();
            }
        }

        return $this->success();
    }
}
