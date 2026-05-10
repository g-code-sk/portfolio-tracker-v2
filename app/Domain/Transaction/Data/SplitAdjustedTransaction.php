<?php

namespace Domain\Transaction\Data;

use App\Models\Transaction;

/**
 * A ledger transaction paired with split-adjusted economics (current share units).
 */
final readonly class SplitAdjustedTransaction
{
    public function __construct(
        public Transaction $transaction,
        public float $numberOfShares,
        public float $pricePerShare,
        public float $totalAmount,
    ) {}

    public static function from(Transaction $transaction, SplitAdjustedTransactionAmounts $amounts): self
    {
        return new self(
            transaction: $transaction,
            numberOfShares: $amounts->numberOfShares,
            pricePerShare: $amounts->pricePerShare,
            totalAmount: $amounts->totalAmount,
        );
    }
}
