<?php

namespace Domain\Transaction\Data;

use App\Models\Transaction;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class WholeShareSegmentResponseData extends Data
{
    public function __construct(
        public int $sourceTransactionId,
        public string $externalTransactionId,
        public string $executedAt,
        public string $ticker,
        public string $name,
        public float $numberOfShares,
        public float $pricePerShare,
        public float $totalAmount,
        public string $currencySymbol,
    ) {}

    public static function fromTransactionShareSlice(Transaction $transaction, float $numberOfShares, float $totalAmount): self
    {
        return new self(
            sourceTransactionId: $transaction->id,
            externalTransactionId: $transaction->external_transaction_id ?? '',
            executedAt: $transaction->executed_at->toIso8601String(),
            ticker: $transaction->security->ticker,
            name: $transaction->security->name,
            numberOfShares: $numberOfShares,
            pricePerShare: (float) $transaction->price_per_share,
            totalAmount: $totalAmount,
            currencySymbol: $transaction->currency->symbol,
        );
    }
}
