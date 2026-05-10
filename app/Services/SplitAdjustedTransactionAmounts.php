<?php

namespace App\Services;

/**
 * Split-adjusted presentation of a fill (quantity scaled up, price scaled down;
 * total notional stays (approximately) the raw quantity × raw price).
 */
final readonly class SplitAdjustedTransactionAmounts
{
    public float $totalAmount;

    public function __construct(
        public float $numberOfShares,
        public float $pricePerShare,
    ) {
        $this->totalAmount = $numberOfShares * $pricePerShare;
    }
}
