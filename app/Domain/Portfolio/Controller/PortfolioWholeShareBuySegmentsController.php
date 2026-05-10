<?php

namespace Domain\Portfolio\Controller;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Models\SecuritySplit;
use App\Models\Transaction;
use App\Services\ApiResponseService;
use App\Services\PortfolioSecuritySplitAdjustmentService;
use Domain\Portfolio\Data\PortfolioWholeShareBuySegmentsQueryData;
use Domain\Transaction\Action\SplitTransactionsAtWholeShareBoundariesAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class PortfolioWholeShareBuySegmentsController extends Controller
{
    public function __invoke(
        Portfolio $portfolio,
        PortfolioWholeShareBuySegmentsQueryData $queryData,
        SplitTransactionsAtWholeShareBoundariesAction $splitTransactionsAtWholeShareBoundaries,
        PortfolioSecuritySplitAdjustmentService $splitAdjustmentService,
        ApiResponseService $apiResponse
    ): JsonResponse {
        Gate::authorize('view', $portfolio);

        $transactions = $this->getTransactions($portfolio, $queryData);
        $splitsForSecurity = $this->getSplitsForSecurity($queryData->securityId);

        $wholeShareGroups = $splitTransactionsAtWholeShareBoundaries->execute(
            $transactions,
            $splitsForSecurity,
            $splitAdjustmentService,
            $portfolio->name,
        );

        return $apiResponse->make(
            message: 'Whole share segments fetched successfully.',
            status: Response::HTTP_OK,
            data: $wholeShareGroups,
        );
    }

    /**
     * @return Collection<int, Transaction>
     */
    private function getTransactions(Portfolio $portfolio, PortfolioWholeShareBuySegmentsQueryData $queryData): Collection
    {
        return Transaction::query()
            ->where('portfolio_id', $portfolio->id)
            ->where('security_id', $queryData->securityId)
            ->where('currency_id', $queryData->currencyId)
            ->with(['security', 'currency', 'type'])
            ->orderBy('executed_at')
            ->get();
    }

    /**
     * @return Collection<int, SecuritySplit>
     */
    private function getSplitsForSecurity(int $securityId): Collection
    {
        return SecuritySplit::query()
            ->where('security_id', $securityId)
            ->orderBy('effective_on')
            ->get();
    }
}
