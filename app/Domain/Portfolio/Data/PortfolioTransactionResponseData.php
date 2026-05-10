<?php

namespace Domain\Portfolio\Data;

use App\Models\Transaction;
use Domain\Transaction\Data\SplitAdjustedTransactionAmounts;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PortfolioTransactionResponseData extends Data
{
    public function __construct(
        public int $id,
        public string $externalTransactionId,
        public string $ticker,
        public string $name,
        public string $typeCode,
        public string $typeName,
        public float $numberOfShares,
        public float $pricePerShare,
        public float $totalAmount,
        public string $currencySymbol,
        public string $executedAt,
    ) {}

    public static function fromTransaction(Transaction $transaction, SplitAdjustedTransactionAmounts $splitAdjustedAmountData): self
    {
        return new self(
            id: $transaction->id,
            externalTransactionId: $transaction->external_transaction_id ?? '',
            ticker: $transaction->security->ticker,
            name: $transaction->security->name,
            typeCode: $transaction->type->code->value,
            typeName: $transaction->type->name,
            numberOfShares: $splitAdjustedAmountData->numberOfShares,
            pricePerShare: $splitAdjustedAmountData->pricePerShare,
            totalAmount: $splitAdjustedAmountData->numberOfShares * $splitAdjustedAmountData->pricePerShare,
            currencySymbol: $transaction->currency->symbol,
            executedAt: $transaction->executed_at->toIso8601String(),
        );
    }
}
