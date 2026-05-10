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

        $transactions = $this->getTransactions($portfolio);

        /** @var array<int, int> $securityIds */
        $securityIds = $transactions->pluck('security_id')->unique()->values()->all();

        if ($securityIds === []) {
            return $apiResponse->make(
                message: 'Portfolio positions fetched successfully.',
                status: Response::HTTP_OK,
                data: new PortfolioPositionsResponseData(positions: []),
            );
        }

        $splitsBySecurityId = $this->getSplitsBySecurityIds($securityIds);

        $portfolioPositions = $this->getPortfolioPositions($transactions, $splitsBySecurityId, $splitAdjustmentService);

        return $apiResponse->make(
            message: 'Portfolio positions fetched successfully.',
            status: Response::HTTP_OK,
            data: new PortfolioPositionsResponseData(positions: $portfolioPositions),
        );
    }

    /**
     * @return Collection<int, Transaction>
     */
    private function getTransactions(Portfolio $portfolio): Collection
    {
        return Transaction::query()
            ->where('portfolio_id', $portfolio->id)
            ->with(['security', 'currency', 'type'])
            ->orderBy('executed_at')
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

    /**
     * @param  Collection<int, Transaction>  $transactionModels
     * @param  Collection<int, Collection<int, SecuritySplit>>  $splitsBySecurityId
     * @return array<int, PortfolioPositionResponseData>
     */
    private function getPortfolioPositions(Collection $transactionModels, Collection $splitsBySecurityId, PortfolioSecuritySplitAdjustmentService $splitAdjustmentService): array
    {
        $transactionsByCurrencyAndSecurity = $transactionModels
            ->groupBy(fn (Transaction $transaction): string => $transaction->currency_id.'-'.$transaction->security_id);

        /** @var Collection<int, PortfolioPositionResponseData> $portfolioPositions */
        $portfolioPositions = collect();

        foreach ($transactionsByCurrencyAndSecurity as $transactions) {
            $securityId = $transactions->first()->security_id;
            $splitsForSecurity = $splitsBySecurityId->get($securityId, collect());

            $portfolioPositionResponseData = PortfolioPositionResponseData::fromTransactions(
                $transactions,
                $splitsForSecurity,
                $splitAdjustmentService,
            );

            $portfolioPositions->push($portfolioPositionResponseData);
        }

        return $portfolioPositions->sortBy(fn (PortfolioPositionResponseData $portfolioPosition): string => $portfolioPosition->ticker)->values()->all();
    }
}
