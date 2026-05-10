<?php

namespace Domain\Portfolio\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PortfolioPositionResponseData extends Data
{
    public function __construct(
        public int $securityId,
        public int $currencyId,
        public string $ticker,
        public string $name,
        public string $currencySymbol,
        public float $sharesBought,
        public float $sharesSold,
        public float $investedAmount,
        public float $soldAmount,
        public float $totalShares,
        public ?float $currentPrice,
        public ?string $currentPriceCurrency,
    ) {}

    public static function fromAggregatedRow(object $row): self
    {
        return new self(
            (int) $row->security_id,
            (int) $row->currency_id,
            $row->ticker,
            $row->name,
            $row->currency,
            (float) $row->shares_bought,
            (float) $row->shares_sold,
            (float) $row->invested_amount,
            (float) $row->sold_amount,
            (float) $row->total_shares,
            $row->current_price !== null ? (float) $row->current_price : null,
            $row->current_price_currency !== null && $row->current_price_currency !== ''
                ? (string) $row->current_price_currency
                : null,
        );
    }
}
