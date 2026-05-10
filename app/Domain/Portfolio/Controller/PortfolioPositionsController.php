<?php

namespace Domain\Portfolio\Controller;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Models\SecuritySplit;
use App\Models\Transaction;
use App\Services\ApiResponseService;
use App\Services\PortfolioSecuritySplitAdjustmentService;
use Domain\Portfolio\Data\PortfolioPositionResponseData;
use Domain\Portfolio\Data\PortfolioPositionsResponseData;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class PortfolioPositionsController extends Controller
{
    public function __invoke(
        Portfolio $portfolio,
        ApiResponseService $apiResponse,
        PortfolioSecuritySplitAdjustmentService $splitAdjustmentService,
    ): JsonResponse {
        Gate::authorize('view', $portfolio);

        $transactionModels = Transaction::query()
            ->where('portfolio_id', $portfolio->id)
            ->with(['security', 'currency', 'type'])
            ->orderBy('executed_at')
            ->get();

        $securityIds = $transactionModels->pluck('security_id')->unique()->values()->all();

        if ($securityIds === []) {
            return $apiResponse->make(
                message: 'Portfolio positions fetched successfully.',
                status: Response::HTTP_OK,
                data: new PortfolioPositionsResponseData(positions: []),
            );
        }

        $splitsBySecurityId = SecuritySplit::query()
            ->whereIn('security_id', $securityIds)
            ->orderBy('effective_on')
            ->get()
            ->groupBy('security_id');

        $transactionsByCurrencyAndSecurity = $transactionModels->groupBy(fn (Transaction $transaction): string => $transaction->currency_id.'-'.$transaction->security_id);

        /** @var Collection<int, PortfolioPositionResponseData> $portfolioPositions */
        $portfolioPositions = collect();

        foreach ($transactionsByCurrencyAndSecurity as $currencyAndSecurity => $transactions) {
            $securityId = $transactions->first()->security_id;
            $splitsForSecurity = $splitsBySecurityId->get($securityId, collect());

            $portfolioPositionResponseData = PortfolioPositionResponseData::fromTransactions(
                $transactions,
                $splitsForSecurity,
            );

            $portfolioPositions->push($portfolioPositionResponseData);
        }

        $positionsSorted = $portfolioPositions->sortBy(fn (PortfolioPositionResponseData $portfolioPosition): string => $portfolioPosition->ticker)->values()->all();

        return $apiResponse->make(
            message: 'Portfolio positions fetched successfully.',
            status: Response::HTTP_OK,
            data: new PortfolioPositionsResponseData(positions: $positionsSorted),
        );
    }
}
