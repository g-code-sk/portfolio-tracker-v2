<?php

namespace Domain\Portfolio\Controller;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Services\ApiResponseService;
use Domain\Transaction\Action\ImportTrading212TransactionsAction;
use Domain\Transaction\Data\TransactionImportPayloadData;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class PortfolioTransactionImportController extends Controller
{
    public function __invoke(
        Portfolio $portfolio,
        TransactionImportPayloadData $data,
        ImportTrading212TransactionsAction $importTrading212TransactionsAction,
        ApiResponseService $apiResponse
    ): JsonResponse {
        Gate::authorize('importTransactions', $portfolio);

        $storedFilePath = $data->file->store(
            'private/transactions/imports',
            'local',
        );

        if ($data->importType->isTrading212Type()) {
            $importTrading212TransactionsAction->execute(
                portfolio: $portfolio,
                storedFilePath: $storedFilePath,
            );
        }

        return $apiResponse->make(
            message: 'Import file uploaded successfully.',
            status: Response::HTTP_CREATED,
        );
    }
}
