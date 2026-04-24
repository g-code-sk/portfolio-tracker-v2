<?php

namespace Domain\Register\Controller;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ApiResponseService;
use Domain\Register\Data\RegisterUserPayloadData;
use Domain\Register\Data\RegisterUserResponseData;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class RegisterController extends Controller
{
    public function __invoke(RegisterUserPayloadData $data, ApiResponseService $apiResponse): JsonResponse
    {
        $user = User::query()->create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
        ]);

        return $apiResponse->make(
            message: 'Registration successful.',
            status: Response::HTTP_CREATED,
            data: RegisterUserResponseData::from($user),
        );
    }
}
