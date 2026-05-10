<?php

namespace Domain\Portfolio\Data;

use Domain\Transaction\WholeShareBucketEpsilon;
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
        public ?float $totalGainLossAmount,
        public ?float $totalReturnPercent,
    ) {}

    public static function fromAggregatedRow(object $row): self
    {
        $currencySymbol = (string) $row->currency;
        $investedAmount = (float) $row->invested_amount;
        $soldAmount = (float) $row->sold_amount;
        $totalShares = (float) $row->total_shares;
        $currentPrice = $row->current_price !== null ? (float) $row->current_price : null;
        $currentPriceCurrency = $row->current_price_currency !== null && $row->current_price_currency !== ''
            ? (string) $row->current_price_currency
            : null;

        $totalGainLossAmount = self::resolveTotalGainLossAmount(
            currencySymbol: $currencySymbol,
            investedAmount: $investedAmount,
            soldAmount: $soldAmount,
            totalShares: $totalShares,
            currentPrice: $currentPrice,
            currentPriceCurrency: $currentPriceCurrency,
        );

        $totalReturnPercent = self::resolveTotalReturnPercent(
            totalGainLossAmount: $totalGainLossAmount,
            investedAmount: $investedAmount,
        );

        return new self(
            (int) $row->security_id,
            (int) $row->currency_id,
            $row->ticker,
            $row->name,
            $currencySymbol,
            (float) $row->shares_bought,
            (float) $row->shares_sold,
            $investedAmount,
            $soldAmount,
            $totalShares,
            $currentPrice,
            $currentPriceCurrency,
            $totalGainLossAmount,
            $totalReturnPercent,
        );
    }

    private static function resolveTotalGainLossAmount(
        string $currencySymbol,
        float $investedAmount,
        float $soldAmount,
        float $totalShares,
        ?float $currentPrice,
        ?string $currentPriceCurrency,
    ): ?float {
        if ($investedAmount <= 0.0) {
            return null;
        }

        if (abs($totalShares) < WholeShareBucketEpsilon::VALUE) {
            return $soldAmount - $investedAmount;
        }

        if ($currentPrice === null) {
            return null;
        }

        if ($currentPriceCurrency === null || $currentPriceCurrency === '') {
            return null;
        }

        if ($currencySymbol !== $currentPriceCurrency) {
            return null;
        }

        return $soldAmount + ($totalShares * $currentPrice) - $investedAmount;
    }

    private static function resolveTotalReturnPercent(
        ?float $totalGainLossAmount,
        float $investedAmount,
    ): ?float {
        if ($totalGainLossAmount === null || $investedAmount <= 0.0) {
            return null;
        }

        return ($totalGainLossAmount / $investedAmount) * 100;
    }
}
