<?php

namespace Domain\Auth\Controller;

use App\Http\Controllers\Controller;
use App\Services\ApiResponseService;
use Domain\Auth\Data\AuthSessionData;
use Domain\Auth\Data\AuthUserSessionResponseData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogoutController extends Controller
{
    public function __invoke(Request $request, ApiResponseService $apiResponse): JsonResponse
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $apiResponse->make(
            message: 'Logout successful.',
            status: Response::HTTP_OK,
            data: new AuthUserSessionResponseData(
                user: null,
                session: AuthSessionData::ended(),
            ),
        );
    }
}
