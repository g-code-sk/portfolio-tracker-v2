<?php

namespace Domain\Portfolio\Data;

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
        public float $numberOfShares,
        public float $pricePerShare,
        public float $totalAmount,
        public string $currencySymbol,
        public string $executedAt,
    ) {}
}
