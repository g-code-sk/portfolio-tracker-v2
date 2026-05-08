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
        public ?string $portfolioName,
        public ?string $ticker,
        public ?string $currencySymbol,
        public ?float $realizedGainLossAmount,
        public ?float $realizedReturnPercent,
    ) {}

    /**
     * @param  array<WholeShareGroupResponseData>  $groups
     */
    public static function fromGroups(array $groups, ?string $portfolioName = null): self
    {
        $firstGroup = $groups[0] ?? null;
        $firstBuySegment = $firstGroup?->buyBucket?->segments[0] ?? null;
        $firstSellSegment = $firstGroup?->sellBucket?->segments[0] ?? null;

        $closedGroups = collect($groups)->filter(
            fn (WholeShareGroupResponseData $group): bool => $group->gainLossAmount !== null && $group->weightedBuyPricePerShare !== null
        );

        $realizedGainLossAmount = null;
        $realizedReturnPercent = null;

        if ($closedGroups->isNotEmpty()) {
            $realizedGainLossAmount = $closedGroups->sum(
                fn (WholeShareGroupResponseData $group): float => (float) $group->gainLossAmount
            );
            $realizedBuyValue = $closedGroups->sum(
                fn (WholeShareGroupResponseData $group): float => (float) $group->weightedBuyPricePerShare
            );

            if ($realizedBuyValue > 0.0) {
                $realizedReturnPercent = ($realizedGainLossAmount / $realizedBuyValue) * 100;
            }
        }

        return new self(
            groups: $groups,
            portfolioName: $portfolioName,
            ticker: $firstBuySegment?->ticker ?? $firstSellSegment?->ticker,
            currencySymbol: $firstSellSegment?->currencySymbol ?? $firstBuySegment?->currencySymbol,
            realizedGainLossAmount: $realizedGainLossAmount,
            realizedReturnPercent: $realizedReturnPercent,
        );
    }
}
