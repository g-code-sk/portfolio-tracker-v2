<?php

namespace Domain\Login\Controller;

use App\Http\Controllers\Controller;
use App\Services\ApiResponseService;
use Domain\Auth\Data\AuthSessionData;
use Domain\Auth\Data\AuthUserSessionResponseData;
use Domain\Login\Data\LoginUserPayloadData;
use Domain\Login\Data\LoginUserResponseData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    public function __invoke(LoginUserPayloadData $data, Request $request, ApiResponseService $apiResponse): JsonResponse
    {
        if (! Auth::attempt([
            'email' => $data->email,
            'password' => $data->password,
        ])) {
            return $apiResponse->make(
                message: 'These credentials do not match our records.',
                status: Response::HTTP_UNAUTHORIZED,
            );
        }

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $user = $request->user();

        if ($user === null) {
            return $apiResponse->make(
                message: 'These credentials do not match our records.',
                status: Response::HTTP_UNAUTHORIZED,
            );
        }

        return $apiResponse->make(
            message: 'Login successful.',
            status: Response::HTTP_OK,
            data: new AuthUserSessionResponseData(
                user: LoginUserResponseData::from($user),
                session: AuthSessionData::active($request),
            ),
        );
    }
}
