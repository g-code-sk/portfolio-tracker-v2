<?php

namespace Domain\Transaction\Data;

use Domain\Transaction\Enums\TransactionTypeCode;
use Illuminate\Support\Collection;

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

    /**
     * @param  Collection<int, SplitAdjustedTransaction>  $adjusted
     */
    public static function fromSplitAdjustedTransactions(Collection $adjusted): self
    {
        $buys = $adjusted->filter(
            fn (SplitAdjustedTransaction $row): bool => $row->transaction->type->code === TransactionTypeCode::Buy
        );
        $sells = $adjusted->filter(
            fn (SplitAdjustedTransaction $row): bool => $row->transaction->type->code === TransactionTypeCode::Sell
        );

        return new self(
            sharesBought: $buys->sum(fn (SplitAdjustedTransaction $row): float => $row->numberOfShares),
            sharesSold: $sells->sum(fn (SplitAdjustedTransaction $row): float => $row->numberOfShares),
            investedAmount: $buys->sum(fn (SplitAdjustedTransaction $row): float => $row->totalAmount),
            soldAmount: $sells->sum(fn (SplitAdjustedTransaction $row): float => $row->totalAmount),
        );
    }

    public function totalShares(): float
    {
        return $this->sharesBought - $this->sharesSold;
    }
}
