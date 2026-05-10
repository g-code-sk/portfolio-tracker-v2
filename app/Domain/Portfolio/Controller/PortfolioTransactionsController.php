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

        $transactionModels = $this->getTransactions($portfolio, $queryData);

        /** @var array<int, int> $securityIds */
        $securityIds = $transactionModels->pluck('security_id')->unique()->values()->all();

        if ($securityIds === []) {
            return $apiResponse->make(
                message: 'Portfolio transactions fetched successfully.',
                status: Response::HTTP_OK,
                data: new PortfolioTransactionsResponseData([], $portfolio->name),
            );
        }

        $splitsBySecurityId = $this->getSplitsBySecurityIds($securityIds);

        $transactions = PortfolioTransactionResponseData::fromTransactions(
            $transactionModels,
            $splitsBySecurityId,
            $splitAdjustmentService,
        );

        return $apiResponse->make(
            message: 'Portfolio transactions fetched successfully.',
            status: Response::HTTP_OK,
            data: new PortfolioTransactionsResponseData(
                $transactions,
                $portfolio->name,
            ),
        );
    }

    /**
     * @return Collection<int, Transaction>
     */
    private function getTransactions(Portfolio $portfolio, PortfolioTransactionsQueryData $queryData): Collection
    {
        $transactionQuery = Transaction::query()
            ->where('portfolio_id', $portfolio->id)
            ->with(['security', 'currency', 'type']);

        if ($queryData->securityId !== null) {
            $transactionQuery->where('security_id', $queryData->securityId);
        }

        if ($queryData->currencyId !== null) {
            $transactionQuery->where('currency_id', $queryData->currencyId);
        }

        return $transactionQuery
            ->orderByDesc('executed_at')
            ->get();
    }

    /**
     * @param  array<int, int>  $securityIds
     * @return Collection<int, Collection<int, SecuritySplit>>
     */
    private function getSplitsBySecurityIds(array $securityIds): Collection
    {
        return SecuritySplit::query()
            ->whereIn('security_id', $securityIds)
            ->orderBy('effective_on')
            ->get()
            ->groupBy('security_id');
    }
}
