<?php

namespace App\Services;

use App\Models\SecuritySplit;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Domain\Transaction\Data\SplitAdjustedTransactionAmounts;
use Illuminate\Support\Collection;

/**
 * Restates historical fills into current share units using stored corporate splits.
 *
 * Improvement ideas (not implemented): choose exchange timezone for same-day split/trade;
 * handle dividend reinvestment special cases; parse raw_ratio when numerator/denominator missing.
 */
class PortfolioSecuritySplitAdjustmentService
{
    /**
     * @param  Collection<int, SecuritySplit>  $splits  splits for one security, oldest first
     */
    public function adjust(Transaction $transaction, Collection $splits): SplitAdjustedTransactionAmounts
    {
        $rawShares = (float) $transaction->number_of_shares;
        $rawPrice = (float) $transaction->price_per_share;

        $multiplier = $this->getCumulativeSplitMultiplier($transaction, $splits);

        $adjustedShares = $rawShares * $multiplier;
        $adjustedPrice = $multiplier > 0.0 ? $rawPrice / $multiplier : $rawPrice;

        return new SplitAdjustedTransactionAmounts(
            numberOfShares: $adjustedShares,
            pricePerShare: $adjustedPrice,
        );
    }

    /**
     * @param  Collection<int, SecuritySplit>  $splits
     */
    private function getCumulativeSplitMultiplier(Transaction $transaction, Collection $splits): float
    {
        $executedDay = CarbonImmutable::parse($transaction->executed_at)->utc()->startOfDay();

        $splitsAfterTrade = $splits->filter(function (SecuritySplit $split) use ($executedDay): bool {
            $splitDay = CarbonImmutable::parse($split->effective_on)->utc()->startOfDay();

            return $splitDay->gt($executedDay);
        });

        $multiplier = 1.0;

        $multiplier = $splitsAfterTrade->reduce(function (float $multiplier, SecuritySplit $split) {
            $numerator = $split->ratio_numerator;
            $denominator = $split->ratio_denominator;

            if ($numerator === null || $denominator === null || $denominator < 1) {
                return $multiplier;
            }

            return $multiplier * $numerator / $denominator;
        }, 1.0);

        return $multiplier;
    }
}
