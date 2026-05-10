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

        $transactions = Transaction::query()
            ->where('portfolio_id', $portfolio->id)
            ->where('security_id', $queryData->securityId)
            ->where('currency_id', $queryData->currencyId)
            ->with(['security', 'currency', 'type'])
            ->orderBy('executed_at')
            ->get();

        $splitsForSecurity = SecuritySplit::query()
            ->where('security_id', $queryData->securityId)
            ->orderBy('effective_on')
            ->get();

        $data = $splitTransactionsAtWholeShareBoundaries->execute(
            $transactions,
            $splitsForSecurity,
            $splitAdjustmentService,
            $portfolio->name,
        );

        return $apiResponse->make(
            message: 'Whole share segments fetched successfully.',
            status: Response::HTTP_OK,
            data: $data,
        );
    }
}
