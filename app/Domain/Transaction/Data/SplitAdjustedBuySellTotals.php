<?php

namespace Domain\Transaction\Data;

/**
 * Split-adjusted aggregates for buy and sell legs of a position (same currency + security slice).
 */
final readonly class SplitAdjustedBuySellTotals
{
    public function __construct(
        public float $sharesBought,
        public float $sharesSold,
        public float $investedAmount,
        public float $soldAmount,
    ) {}

    public function totalShares(): float
    {
        return $this->sharesBought - $this->sharesSold;
    }
}
