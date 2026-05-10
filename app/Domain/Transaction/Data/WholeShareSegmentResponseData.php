<?php

namespace Domain\Transaction\Data;

use App\Models\Transaction;
use Domain\Transaction\WholeShareBucketEpsilon;
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

    public static function fromTransactionShareSlice(
        Transaction $transaction,
        float $segmentShareCount,
        float $segmentTotalAmount,
        float $transactionShareCount,
        float $transactionTotalAmount,
    ): self {
        $pricePerShare = $transactionShareCount > WholeShareBucketEpsilon::VALUE
            ? $transactionTotalAmount / $transactionShareCount
            : (float) $transaction->price_per_share;

        return new self(
            sourceTransactionId: $transaction->id,
            externalTransactionId: $transaction->external_transaction_id ?? '',
            executedAt: $transaction->executed_at->toIso8601String(),
            ticker: $transaction->security->ticker,
            name: $transaction->security->name,
            numberOfShares: $segmentShareCount,
            pricePerShare: $pricePerShare,
            totalAmount: $segmentTotalAmount,
            currencySymbol: $transaction->currency->symbol,
        );
    }
}
