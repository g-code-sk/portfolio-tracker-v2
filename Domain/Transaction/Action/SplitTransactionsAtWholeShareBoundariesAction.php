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
        $buyTransactions = $transactionsAscending
            ->filter(fn (Transaction $transaction): bool => $transaction->type->code === TransactionTypeCode::Buy)
            ->values();

        $sellTransactions = $transactionsAscending
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

        $groups = [];

        foreach ($groupIndices as $groupIndex) {
            $buyBucket = $buyBuckets[$groupIndex] ?? null;
            $sellBucket = $sellBuckets[$groupIndex] ?? null;
            $buyCompletedAt = $this->resolveMaxExecutedAt($buyBucket);
            $soldAt = $this->resolveMinExecutedAt($sellBucket);
            $daysToSell = null;

            if ($buyCompletedAt !== null && $soldAt !== null) {
                $daysToSell = CarbonImmutable::parse($buyCompletedAt)->diffInDays(CarbonImmutable::parse($soldAt), false);
            }

            $groups[] = new WholeShareGroupResponseData(
                groupIndex: (int) $groupIndex,
                buyBucket: $buyBucket,
                sellBucket: $sellBucket,
                buyCompletedAt: $buyCompletedAt,
                soldAt: $soldAt,
                daysToSell: $daysToSell,
            );
        }

        return new WholeShareGroupsResponseData(groups: $groups);
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
}
