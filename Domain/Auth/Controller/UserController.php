<?php

namespace Domain\Auth\Controller;

use App\Http\Controllers\Controller;
use App\Services\ApiResponseService;
use Domain\Auth\Data\AuthSessionData;
use Domain\Auth\Data\AuthUserSessionResponseData;
use Domain\Login\Data\LoginUserResponseData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function __invoke(Request $request, ApiResponseService $apiResponse): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $apiResponse->make(
                message: 'Unauthenticated.',
                status: Response::HTTP_UNAUTHORIZED,
            );
        }

        return $apiResponse->make(
            message: 'Authenticated user fetched successfully.',
            status: Response::HTTP_OK,
            data: new AuthUserSessionResponseData(
                user: LoginUserResponseData::from($user),
                session: AuthSessionData::active($request),
            ),
        );
    }
}
