<?php

namespace Domain\Transaction\Controller;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Services\ApiResponseService;
use Domain\Transaction\Data\TransactionImportTypesResponseData;
use Domain\Transaction\Enums\TransactionImportType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class TransactionImportTypeController extends Controller
{
    public function __invoke(
        Portfolio $portfolio,
        ApiResponseService $apiResponse
    ): JsonResponse {
        Gate::authorize('importTransactions', $portfolio);

        return $apiResponse->make(
            message: 'Import types fetched successfully.',
            status: Response::HTTP_OK,
            data: new TransactionImportTypesResponseData(importTypes: TransactionImportType::values()),
        );
    }
}
