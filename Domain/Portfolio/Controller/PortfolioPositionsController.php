<?php

namespace Domain\Portfolio\Controller;

use App\Enums\SecurityTypeCode;
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
            ->where('transactions.portfolio_id', $portfolio->id)
            ->join('securities', 'securities.id', '=', 'transactions.security_id')
            ->join('currencies', 'currencies.id', '=', 'transactions.currency_id')
            ->join('security_types', 'security_types.id', '=', 'transactions.type_id')
            ->selectRaw(
                '
                transactions.security_id as security_id,
                securities.ticker as ticker,
                securities.name as name,
                currencies.symbol as currency,
                SUM(
                    CASE
                        WHEN security_types.code = ? THEN transactions.number_of_shares
                        ELSE -transactions.number_of_shares
                    END
                ) as total_shares
                ',
                [SecurityTypeCode::Buy->value]
            )
            ->groupBy(
                'transactions.security_id',
                'securities.ticker',
                'securities.name',
                'transactions.currency_id',
                'currencies.symbol',
            )
            ->orderBy('securities.ticker')
            ->get();

        $positions = $aggregatedRows->map(
            fn (object $aggregatedRow): PortfolioPositionResponseData => new PortfolioPositionResponseData(
                securityId: (int) $aggregatedRow->security_id,
                ticker: $aggregatedRow->ticker,
                name: $aggregatedRow->name,
                currency: $aggregatedRow->currency,
                totalShares: (float) $aggregatedRow->total_shares,
            )
        );

        return $apiResponse->make(
            message: 'Portfolio positions fetched successfully.',
            status: Response::HTTP_OK,
            data: new PortfolioPositionsResponseData(positions: $positions->all()),
        );
    }
}
