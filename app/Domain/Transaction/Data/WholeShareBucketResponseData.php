<?php

namespace Domain\Transaction\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class WholeShareBucketResponseData extends Data
{
    public function __construct(
        public int $wholeShareBucketIndex,
        #[DataCollectionOf(WholeShareSegmentResponseData::class)]
        public array $segments,
    ) {}

    public function hasSegments(): bool
    {
        return $this->segments !== [];
    }

    public function isEmpty(): bool
    {
        return ! $this->hasSegments();
    }
}
