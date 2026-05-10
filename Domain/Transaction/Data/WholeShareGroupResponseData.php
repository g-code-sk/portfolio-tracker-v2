<?php

namespace Domain\Transaction\Data;

use Carbon\CarbonImmutable;
use Domain\Transaction\WholeShareBucketEpsilon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class WholeShareGroupResponseData extends Data
{
    public function __construct(
        public int $groupIndex,
        public ?WholeShareBucketResponseData $buyBucket,
        public ?WholeShareBucketResponseData $sellBucket,
        public ?string $buyDate,
        public ?string $sellDate,
        public ?int $holdPeriodDays,
        public ?float $weightedBuyPricePerShare,
        public ?float $weightedSellPricePerShare,
        public ?float $returnPercent,
        public ?float $gainLossAmount,
        public ?bool $isSellTaxable,
    ) {}

    public static function fromBuckets(int $groupIndex, ?WholeShareBucketResponseData $buyBucket, ?WholeShareBucketResponseData $sellBucket): self
    {
        $buyDate = self::resolveMaxExecutedAt($buyBucket);
        $sellDate = self::resolveMinExecutedAt($sellBucket);
        $holdPeriodDays = null;
        $weightedBuyPricePerShare = self::resolveWeightedPricePerShare($buyBucket);
        $weightedSellPricePerShare = self::resolveWeightedPricePerShare($sellBucket);
        $returnPercent = self::resolveReturnPercent($weightedBuyPricePerShare, $weightedSellPricePerShare);
        $gainLossAmount = self::resolveGainLossAmount($buyBucket, $sellBucket);
        $isSellTaxable = self::resolveIsSellTaxable($buyDate, $sellDate);

        if ($buyDate !== null && $sellDate !== null) {
            $holdPeriodDays = (int) CarbonImmutable::parse($buyDate)->startOfDay()->diffInDays(CarbonImmutable::parse($sellDate)->startOfDay(), false);
        }

        return new self(
            groupIndex: $groupIndex,
            buyBucket: $buyBucket,
            sellBucket: $sellBucket,
            buyDate: $buyDate,
            sellDate: $sellDate,
            holdPeriodDays: $holdPeriodDays,
            weightedBuyPricePerShare: $weightedBuyPricePerShare,
            weightedSellPricePerShare: $weightedSellPricePerShare,
            returnPercent: $returnPercent,
            gainLossAmount: $gainLossAmount,
            isSellTaxable: $isSellTaxable,
        );
    }

    private static function resolveMaxExecutedAt(?WholeShareBucketResponseData $bucket): ?string
    {
        if ($bucket === null || $bucket->isEmpty()) {
            return null;
        }

        return collect($bucket->segments)
            ->map(fn (WholeShareSegmentResponseData $segment): string => $segment->executedAt)
            ->max();
    }

    private static function resolveMinExecutedAt(?WholeShareBucketResponseData $bucket): ?string
    {
        if ($bucket === null || $bucket->isEmpty()) {
            return null;
        }

        return collect($bucket->segments)
            ->map(fn (WholeShareSegmentResponseData $segment): string => $segment->executedAt)
            ->min();
    }

    private static function resolveWeightedPricePerShare(?WholeShareBucketResponseData $bucket): ?float
    {
        if ($bucket === null || $bucket->isEmpty()) {
            return null;
        }

        $totalShares = collect($bucket->segments)
            ->sum(fn (WholeShareSegmentResponseData $segment): float => $segment->numberOfShares);

        if ($totalShares <= WholeShareBucketEpsilon::VALUE) {
            return null;
        }

        $totalAmount = collect($bucket->segments)
            ->sum(fn (WholeShareSegmentResponseData $segment): float => $segment->numberOfShares * $segment->pricePerShare);

        return $totalAmount / $totalShares;
    }

    private static function resolveReturnPercent(?float $weightedBuyPricePerShare, ?float $weightedSellPricePerShare): ?float
    {
        if ($weightedBuyPricePerShare === null || $weightedSellPricePerShare === null) {
            return null;
        }

        if ($weightedBuyPricePerShare <= 0.0) {
            return null;
        }

        return (($weightedSellPricePerShare - $weightedBuyPricePerShare) / $weightedBuyPricePerShare) * 100;
    }

    private static function resolveGainLossAmount(?WholeShareBucketResponseData $buyBucket, ?WholeShareBucketResponseData $sellBucket): ?float
    {
        if ($buyBucket === null || $sellBucket === null) {
            return null;
        }

        if ($buyBucket->isEmpty() || $sellBucket->isEmpty()) {
            return null;
        }

        $buyTotalAmount = collect($buyBucket->segments)
            ->sum(fn (WholeShareSegmentResponseData $segment): float => $segment->totalAmount);
        $sellTotalAmount = collect($sellBucket->segments)
            ->sum(fn (WholeShareSegmentResponseData $segment): float => $segment->totalAmount);

        return $sellTotalAmount - $buyTotalAmount;
    }

    private static function resolveIsSellTaxable(?string $buyDate, ?string $sellDate): ?bool
    {
        if ($buyDate === null) {
            return null;
        }

        $buyDateValue = CarbonImmutable::parse($buyDate)->startOfDay();

        $effectiveSellDate = $sellDate !== null
            ? CarbonImmutable::parse($sellDate)->startOfDay()
            : CarbonImmutable::now()->startOfDay();

        $taxFreeFromDate = $buyDateValue->addYear()->addDay();

        return $effectiveSellDate->lt($taxFreeFromDate);
    }
}
