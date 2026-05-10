<?php

namespace Domain\Portfolio\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PortfolioTransactionsResponseData extends Data
{
    public function __construct(
        #[DataCollectionOf(PortfolioTransactionResponseData::class)]
        public array $transactions,
        public ?string $portfolioName,
        public ?string $securityTicker,
        public ?string $securityName,
        public ?string $currencySymbol,
    ) {}
}
