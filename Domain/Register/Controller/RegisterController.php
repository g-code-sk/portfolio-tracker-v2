<?php

namespace Domain\Register\Controller;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ApiResponseService;
use Domain\Login\Data\LoginUserResponseData;
use Domain\Register\Data\RegisterUserPayloadData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class RegisterController extends Controller
{
    public function __invoke(RegisterUserPayloadData $data, Request $request, ApiResponseService $apiResponse): JsonResponse
    {
        $user = User::query()->create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
        ]);

        Auth::login($user);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return $apiResponse->make(
            message: 'Registration successful.',
            status: Response::HTTP_CREATED,
            data: LoginUserResponseData::from($user),
        );
    }
}
