<?php

namespace Domain\Transaction\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class WholeShareBuySegmentResponseData extends Data
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
}
