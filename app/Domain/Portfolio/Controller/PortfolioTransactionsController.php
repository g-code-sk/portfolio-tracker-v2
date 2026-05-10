<?php

namespace Domain\Portfolio\Controller;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Models\SecuritySplit;
use App\Models\Transaction;
use App\Services\ApiResponseService;
use App\Services\PortfolioSecuritySplitAdjustmentService;
use Domain\Portfolio\Data\PortfolioTransactionResponseData;
use Domain\Portfolio\Data\PortfolioTransactionsQueryData;
use Domain\Portfolio\Data\PortfolioTransactionsResponseData;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class PortfolioTransactionsController extends Controller
{
    public function __invoke(
        Portfolio $portfolio,
        PortfolioTransactionsQueryData $queryData,
        ApiResponseService $apiResponse,
        PortfolioSecuritySplitAdjustmentService $splitAdjustmentService,
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

        // Single query for splits on every distinct security in this page — not N+1 per transaction.
        // Improvement: cache split timelines per security across requests if this endpoint becomes hot.
        $securityIds = $transactionModels->pluck('security_id')->unique()->values()->all();
        $splitsBySecurityId = collect();

        if ($securityIds !== []) {
            $splitsBySecurityId = SecuritySplit::query()
                ->whereIn('security_id', $securityIds)
                ->orderBy('effective_on')
                ->get()
                ->groupBy('security_id');
        }

        $transactions = $transactionModels
            ->map(function (Transaction $transaction) use ($splitAdjustmentService, $splitsBySecurityId): PortfolioTransactionResponseData {

                /** @var Collection<int, SecuritySplit> $splitsForSecurity */
                $splitsForSecurity = $splitsBySecurityId->get($transaction->security_id, collect());
                $splitAdjustedAmountData = $splitAdjustmentService->adjust($transaction, $splitsForSecurity);

                return PortfolioTransactionResponseData::fromTransaction($transaction, $splitAdjustedAmountData);
            });

        return $apiResponse->make(
            message: 'Portfolio transactions fetched successfully.',
            status: Response::HTTP_OK,
            data: new PortfolioTransactionsResponseData(
                transactions: $transactions->all(),
                portfolioName: $portfolio->name,
            ),
        );
    }
}
