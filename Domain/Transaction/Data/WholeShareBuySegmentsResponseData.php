<?php

namespace Domain\Transaction\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class WholeShareBuySegmentsResponseData extends Data
{
    public function __construct(
        #[DataCollectionOf(WholeShareBuyBucketResponseData::class)]
        public array $buckets,
    ) {}
}
