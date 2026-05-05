<?php

namespace Domain\Transaction\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class WholeShareBuyBucketResponseData extends Data
{
    public function __construct(
        public int $wholeShareBucketIndex,
        #[DataCollectionOf(WholeShareBuySegmentResponseData::class)]
        public array $segments,
    ) {}
}
