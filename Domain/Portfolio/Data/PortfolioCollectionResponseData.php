<?php

namespace Domain\Portfolio\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PortfolioCollectionResponseData extends Data
{
    public function __construct(
        #[DataCollectionOf(PortfolioResponseData::class)]
        public array $portfolios,
    ) {}
}
