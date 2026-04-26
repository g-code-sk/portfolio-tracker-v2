<?php

namespace Domain\Portfolio\Controller;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Services\ApiResponseService;
use Domain\Transaction\Data\TransactionImportPayloadData;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class PortfolioTransactionImportController extends Controller
{
    public function __invoke(
        Portfolio $portfolio,
        TransactionImportPayloadData $data,
        ApiResponseService $apiResponse
    ): JsonResponse {
        Gate::authorize('import', $portfolio);

        $storedFilePath = $data->file->store(
            'private/transactions/imports',
            'local',
        );

        return $apiResponse->make(
            message: 'Import file uploaded successfully.',
            status: Response::HTTP_CREATED,
        );
    }
}
