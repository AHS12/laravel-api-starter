<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\FallbackLoginRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LogoutRequest;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Resources\Auth\UserResource;
use App\Http\Services\Auth\AuthService;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AuthController extends Controller
{

    protected $authService;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }


    /**
     * Send OTP to a user in order to login in the app
     *
     * @param  SendOtpRequest $request
     * @return JsonResponse
     */
    public function sendOtp(SendOtpRequest $request)
    {
        $user = User::where('phone', $request->phone)->first();
        $this->authService->sendOtpToUser($user, $request->firstLogin, $request->pin);
        return $this->sendSuccessResponse(
            [],
            __('messages.otp.sent'),
            Response::HTTP_OK
        );
    }


    /**
     * log the user in after verifying the otp
     *
     * @param  LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request)
    {
        $user = User::where('phone', $request->phone)->first();
        $authData = $this->authService->login(
            $user,
            $request->otp,
            $request->device,
            false,
        );
        $data = [
            'user' => UserResource::make($authData['user']),
            'token' => $authData['token'],
        ];
        return $this->sendSuccessResponse(
            $data,
            __('messages.login.success'),
            Response::HTTP_OK
        );
    }

    /**
     * log the user in with traditional phone and pin. no OTP required.
     *
     * @param  FallbackLoginRequest $request
     * @return JsonResponse
     */
    public function fallbackLogin(FallbackLoginRequest $request)
    {

        $user = User::where('phone', $request->phone)
            ->with('roles.permissions')
            ->first();
        $authData = $this->authService->login(
            $user,
            $request->pin,
            $request->device,
            true,
        );
        $data = [
            'user' => UserResource::make($authData['user']),
            'token' => $authData['token'],
        ];
        return $this->sendSuccessResponse(
            $data,
            __('messages.login.success'),
            Response::HTTP_OK
        );
    }

    public function logout(LogoutRequest $request)
    {
        $this->authService->logout($request->device);
        return new JsonResponse([], 204);
    }
}