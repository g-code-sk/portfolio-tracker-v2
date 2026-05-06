<?php

namespace Domain\Transaction\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class WholeShareGroupResponseData extends Data
{
    public function __construct(
        public int $groupIndex,
        public ?WholeShareBucketResponseData $buyBucket,
        public ?WholeShareBucketResponseData $sellBucket,
        public ?string $buyDate,
        public ?string $sellDate,
        public ?int $holdPeriodDays,
        public ?float $weightedBuyPricePerShare,
        public ?float $weightedSellPricePerShare,
        public ?float $yieldPercent,
        public ?float $yieldAmount,
        public ?bool $isSellTaxable,
    ) {}
}
