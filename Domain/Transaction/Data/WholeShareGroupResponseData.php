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
        public ?string $buyCompletedAt,
        public ?string $soldAt,
        public ?int $daysToSell,
    ) {}
}
