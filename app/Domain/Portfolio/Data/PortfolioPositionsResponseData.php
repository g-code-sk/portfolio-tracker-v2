<?php

namespace Domain\Portfolio\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PortfolioPositionsResponseData extends Data
{
    public function __construct(
        #[DataCollectionOf(PortfolioPositionResponseData::class)]
        public array $positions,
    ) {}
}
