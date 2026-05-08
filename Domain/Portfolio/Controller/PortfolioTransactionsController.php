<?php

namespace Domain\Portfolio\Controller;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Models\Transaction;
use App\Services\ApiResponseService;
use Domain\Portfolio\Data\PortfolioTransactionResponseData;
use Domain\Portfolio\Data\PortfolioTransactionsQueryData;
use Domain\Portfolio\Data\PortfolioTransactionsResponseData;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class PortfolioTransactionsController extends Controller
{
    public function __invoke(
        Portfolio $portfolio,
        PortfolioTransactionsQueryData $queryData,
        ApiResponseService $apiResponse
    ): JsonResponse {
        Gate::authorize('view', $portfolio);

        $transactionQuery = Transaction::query()
            ->where('portfolio_id', $portfolio->id)
            ->with(['security', 'currency', 'type']);

        if ($queryData->securityId !== null) {
            $transactionQuery->where('security_id', $queryData->securityId);
        }

        if ($queryData->currencyId !== null) {
            $transactionQuery->where('currency_id', $queryData->currencyId);
        }

        $transactionModels = $transactionQuery
            ->orderByDesc('executed_at')
            ->get();

        $transactions = $transactionModels
            ->map(function (Transaction $transaction): PortfolioTransactionResponseData {
                return PortfolioTransactionResponseData::fromTransaction($transaction);
            });

        $metadataTransaction = $transactionModels->first();

        return $apiResponse->make(
            message: 'Portfolio transactions fetched successfully.',
            status: Response::HTTP_OK,
            data: new PortfolioTransactionsResponseData(
                transactions: $transactions->all(),
                portfolioName: $portfolio->name,
                securityTicker: $metadataTransaction?->security->ticker,
                securityName: $metadataTransaction?->security->name,
                currencySymbol: $metadataTransaction?->currency->symbol,
            ),
        );
    }
}
