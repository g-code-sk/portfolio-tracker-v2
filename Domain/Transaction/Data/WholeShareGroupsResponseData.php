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
        public ?float $realizedGainLossAmount,
        public ?float $realizedReturnPercent,
    ) {}

    /**
     * @param  array<WholeShareGroupResponseData>  $groups
     */
    public static function fromGroups(array $groups): self
    {
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
            realizedGainLossAmount: $realizedGainLossAmount,
            realizedReturnPercent: $realizedReturnPercent,
        );
    }
}
