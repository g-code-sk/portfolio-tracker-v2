<?php

namespace Domain\Portfolio\Controller;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Models\Transaction;
use App\Services\ApiResponseService;
use Domain\Portfolio\Data\PortfolioPositionResponseData;
use Domain\Portfolio\Data\PortfolioPositionsResponseData;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class PortfolioPositionsController extends Controller
{
    public function __invoke(
        Portfolio $portfolio,
        ApiResponseService $apiResponse
    ): JsonResponse {
        Gate::authorize('view', $portfolio);

        $aggregatedRows = Transaction::query()
            ->portfolioPositionMetrics($portfolio->id)
            ->get();

        $positions = $aggregatedRows->map(
            fn (object $aggregatedRow): PortfolioPositionResponseData => new PortfolioPositionResponseData(
                (int) $aggregatedRow->security_id,
                (int) $aggregatedRow->currency_id,
                $aggregatedRow->ticker,
                $aggregatedRow->name,
                $aggregatedRow->currency,
                (float) $aggregatedRow->shares_bought,
                (float) $aggregatedRow->shares_sold,
                (float) $aggregatedRow->invested_amount,
                (float) $aggregatedRow->sold_amount,
                (float) $aggregatedRow->total_shares,
            )
        );

        return $apiResponse->make(
            message: 'Portfolio positions fetched successfully.',
            status: Response::HTTP_OK,
            data: new PortfolioPositionsResponseData(positions: $positions->all()),
        );
    }
}
