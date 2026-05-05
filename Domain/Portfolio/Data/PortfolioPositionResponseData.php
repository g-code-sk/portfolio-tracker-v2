<?php

namespace Domain\Portfolio\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PortfolioPositionResponseData extends Data
{
    public function __construct(
        public int $securityId,
        public string $ticker,
        public string $name,
        public string $currency,
        public float $sharesBought,
        public float $sharesSold,
        public float $investedAmount,
        public float $soldAmount,
        public float $totalShares,
    ) {}
}
