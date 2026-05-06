<?php

namespace Domain\Transaction\Action;

use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Domain\Transaction\Data\WholeShareBucketResponseData;
use Domain\Transaction\Data\WholeShareGroupResponseData;
use Domain\Transaction\Data\WholeShareGroupsResponseData;
use Domain\Transaction\Data\WholeShareSegmentResponseData;
use Domain\Transaction\Enums\TransactionTypeCode;
use Illuminate\Support\Collection;

class SplitTransactionsAtWholeShareBoundariesAction
{
    private const float BUCKET_EPSILON = 1e-9;

    /**
     * @param  Collection<int, Transaction>  $transactionsAscending
     */
    public function execute(Collection $transactionsAscending): WholeShareGroupsResponseData
    {
        $groups = [];
        $globalGroupIndex = 0;

        foreach ($this->partitionIntoPositionCycles($transactionsAscending) as $cycleTransactions) {
            $buyTransactions = $cycleTransactions
                ->filter(fn (Transaction $transaction): bool => $transaction->type->code === TransactionTypeCode::Buy)
                ->values();

            $sellTransactions = $cycleTransactions
                ->filter(fn (Transaction $transaction): bool => $transaction->type->code === TransactionTypeCode::Sell)
                ->values();

            $buyBuckets = $this->splitIntoWholeShareBuckets($buyTransactions);
            $sellBuckets = $this->splitIntoWholeShareBuckets($sellTransactions);

            /** @var list<int> $groupIndices */
            $groupIndices = collect(array_keys($buyBuckets))
                ->merge(array_keys($sellBuckets))
                ->unique()
                ->sort()
                ->values()
                ->all();

            foreach ($groupIndices as $groupIndex) {
                $buyBucket = $buyBuckets[$groupIndex] ?? null;
                $sellBucket = $sellBuckets[$groupIndex] ?? null;
                $buyDate = $this->resolveMaxExecutedAt($buyBucket);
                $sellDate = $this->resolveMinExecutedAt($sellBucket);
                $holdPeriodDays = null;
                $weightedBuyPricePerShare = $this->resolveWeightedPricePerShare($buyBucket);
                $weightedSellPricePerShare = $this->resolveWeightedPricePerShare($sellBucket);
                $yieldPercent = $this->resolveYieldPercent($weightedBuyPricePerShare, $weightedSellPricePerShare);
                $yieldAmount = $this->resolveYieldAmount($buyBucket, $sellBucket);
                $isSellTaxable = $this->resolveIsSellTaxable($buyDate, $sellDate);

                if ($buyDate !== null && $sellDate !== null) {
                    $holdPeriodDays = CarbonImmutable::parse($buyDate)->diffInDays(CarbonImmutable::parse($sellDate), false);
                }

                $groups[] = new WholeShareGroupResponseData(
                    groupIndex: $globalGroupIndex,
                    buyBucket: $buyBucket,
                    sellBucket: $sellBucket,
                    buyDate: $buyDate,
                    sellDate: $sellDate,
                    holdPeriodDays: $holdPeriodDays,
                    weightedBuyPricePerShare: $weightedBuyPricePerShare,
                    weightedSellPricePerShare: $weightedSellPricePerShare,
                    yieldPercent: $yieldPercent,
                    yieldAmount: $yieldAmount,
                    isSellTaxable: $isSellTaxable,
                );

                $globalGroupIndex++;
            }
        }

        return new WholeShareGroupsResponseData(groups: $groups);
    }

    /**
     * @param  Collection<int, Transaction>  $transactionsAscending
     * @return list<Collection<int, Transaction>>
     */
    private function partitionIntoPositionCycles(Collection $transactionsAscending): array
    {
        /** @var list<Collection<int, Transaction>> $cycles */
        $cycles = [];
        $runningShares = 0.0;
        $currentCycleTransactions = collect();

        foreach ($transactionsAscending as $transaction) {
            $shares = (float) $transaction->number_of_shares;

            if ($shares <= self::BUCKET_EPSILON) {
                continue;
            }

            if ($transaction->type->code === TransactionTypeCode::Buy) {
                if ($this->isApproximatelyZero($runningShares) && $currentCycleTransactions->isNotEmpty()) {
                    $cycles[] = $currentCycleTransactions->values();
                    $currentCycleTransactions = collect();
                }

                $currentCycleTransactions->push($transaction);
                $runningShares += $shares;

                continue;
            }

            if ($transaction->type->code === TransactionTypeCode::Sell) {
                $currentCycleTransactions->push($transaction);
                $runningShares -= $shares;

                if ($this->isApproximatelyZero($runningShares)) {
                    $runningShares = 0.0;
                    $cycles[] = $currentCycleTransactions->values();
                    $currentCycleTransactions = collect();
                }
            }
        }

        if ($currentCycleTransactions->isNotEmpty()) {
            $cycles[] = $currentCycleTransactions->values();
        }

        return $cycles;
    }

    private function isApproximatelyZero(float $value): bool
    {
        return abs($value) <= self::BUCKET_EPSILON;
    }

    /**
     * @param  Collection<int, Transaction>  $transactionsAscending
     * @return array<int, WholeShareBucketResponseData>
     */
    private function splitIntoWholeShareBuckets(Collection $transactionsAscending): array
    {
        /** @var array<int, list<WholeShareSegmentResponseData>> $bucketSegments */
        $bucketSegments = [];
        $bucketIndex = 0;
        $bucketFill = 0.0;

        foreach ($transactionsAscending as $transaction) {
            $transactionShareCount = (float) $transaction->number_of_shares;

            if ($transactionShareCount <= self::BUCKET_EPSILON) {
                continue;
            }

            $transactionAmount = $transactionShareCount * (float) $transaction->price_per_share;
            $remainingShareCount = $transactionShareCount;

            while ($remainingShareCount > self::BUCKET_EPSILON) {
                $roomInBucket = 1.0 - $bucketFill;
                $take = min($remainingShareCount, $roomInBucket);
                $sliceTotalAmount = $transactionAmount * ($take / $transactionShareCount);

                $segment = new WholeShareSegmentResponseData(
                    sourceTransactionId: $transaction->id,
                    externalTransactionId: $transaction->external_transaction_id ?? '',
                    executedAt: $transaction->executed_at->toDateString(),
                    ticker: $transaction->security->ticker,
                    name: $transaction->security->name,
                    numberOfShares: $take,
                    pricePerShare: (float) $transaction->price_per_share,
                    totalAmount: $sliceTotalAmount,
                    currencySymbol: $transaction->currency->symbol,
                );

                $bucketSegments[$bucketIndex] ??= [];
                $bucketSegments[$bucketIndex][] = $segment;

                $bucketFill += $take;
                $remainingShareCount -= $take;

                if ($bucketFill >= 1.0 - self::BUCKET_EPSILON) {
                    $bucketFill = 0.0;
                    $bucketIndex++;
                }
            }
        }

        ksort($bucketSegments);

        $buckets = [];

        foreach ($bucketSegments as $index => $segments) {
            $buckets[(int) $index] = new WholeShareBucketResponseData(
                wholeShareBucketIndex: (int) $index,
                segments: $segments,
            );
        }

        return $buckets;
    }

    private function resolveMaxExecutedAt(?WholeShareBucketResponseData $bucket): ?string
    {
        if ($bucket === null || $bucket->segments === []) {
            return null;
        }

        return collect($bucket->segments)
            ->map(fn (WholeShareSegmentResponseData $segment): string => $segment->executedAt)
            ->max();
    }

    private function resolveMinExecutedAt(?WholeShareBucketResponseData $bucket): ?string
    {
        if ($bucket === null || $bucket->segments === []) {
            return null;
        }

        return collect($bucket->segments)
            ->map(fn (WholeShareSegmentResponseData $segment): string => $segment->executedAt)
            ->min();
    }

    private function resolveWeightedPricePerShare(?WholeShareBucketResponseData $bucket): ?float
    {
        if ($bucket === null || $bucket->segments === []) {
            return null;
        }

        $totalShares = collect($bucket->segments)
            ->sum(fn (WholeShareSegmentResponseData $segment): float => $segment->numberOfShares);

        if ($totalShares <= self::BUCKET_EPSILON) {
            return null;
        }

        $totalAmount = collect($bucket->segments)
            ->sum(fn (WholeShareSegmentResponseData $segment): float => $segment->numberOfShares * $segment->pricePerShare);

        return $totalAmount / $totalShares;
    }

    private function resolveYieldPercent(?float $weightedBuyPricePerShare, ?float $weightedSellPricePerShare): ?float
    {
        if ($weightedBuyPricePerShare === null || $weightedSellPricePerShare === null) {
            return null;
        }

        if ($weightedBuyPricePerShare <= 0.0) {
            return null;
        }

        return (($weightedSellPricePerShare - $weightedBuyPricePerShare) / $weightedBuyPricePerShare) * 100;
    }

    private function resolveYieldAmount(?WholeShareBucketResponseData $buyBucket, ?WholeShareBucketResponseData $sellBucket): ?float
    {
        if ($buyBucket === null || $sellBucket === null) {
            return null;
        }

        if ($buyBucket->segments === [] || $sellBucket->segments === []) {
            return null;
        }

        $buyTotalAmount = collect($buyBucket->segments)
            ->sum(fn (WholeShareSegmentResponseData $segment): float => $segment->totalAmount);
        $sellTotalAmount = collect($sellBucket->segments)
            ->sum(fn (WholeShareSegmentResponseData $segment): float => $segment->totalAmount);

        return $sellTotalAmount - $buyTotalAmount;
    }

    private function resolveIsSellTaxable(?string $buyDate, ?string $sellDate): ?bool
    {
        if ($buyDate === null) {
            return null;
        }

        $buyDateValue = CarbonImmutable::parse($buyDate);

        $effectiveSellDate = $sellDate !== null
            ? CarbonImmutable::parse($sellDate)
            : CarbonImmutable::now()->startOfDay();
            
        $taxFreeFromDate = $buyDateValue->addYear()->addDay();

        return $effectiveSellDate->lt($taxFreeFromDate);
    }
}
