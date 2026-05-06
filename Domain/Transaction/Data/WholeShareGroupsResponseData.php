<?php

namespace Domain\Transaction\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class WholeShareGroupsResponseData extends Data
{
    public function __construct(
        #[DataCollectionOf(WholeShareGroupResponseData::class)]
        public array $groups,
    ) {}
}
