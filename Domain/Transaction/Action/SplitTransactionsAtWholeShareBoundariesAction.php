<?php

namespace Domain\Transaction\Action;

use App\Models\Transaction;
use Domain\Transaction\Data\WholeShareBucketResponseData;
use Domain\Transaction\Data\WholeShareGroupResponseData;
use Domain\Transaction\Data\WholeShareGroupsResponseData;
use Domain\Transaction\Data\WholeShareSegmentResponseData;
use Domain\Transaction\Enums\TransactionTypeCode;
use Domain\Transaction\Services\TransactionPositionCyclePartitionerService;
use Domain\Transaction\WholeShareBucketEpsilon;
use Illuminate\Support\Collection;

class SplitTransactionsAtWholeShareBoundariesAction
{
    /**
     * @param  Collection<int, Transaction>  $transactionsAscending
     */
    public function execute(Collection $transactionsAscending): WholeShareGroupsResponseData
    {
        $groups = [];
        $globalGroupIndex = 0;

        $cycles = TransactionPositionCyclePartitionerService::partition($transactionsAscending);

        foreach ($cycles as $cycleTransactions) {
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

                $groups[] = WholeShareGroupResponseData::fromBuckets($globalGroupIndex, $buyBucket, $sellBucket);

                $globalGroupIndex++;
            }
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

            if ($transactionShareCount <= WholeShareBucketEpsilon::VALUE) {
                continue;
            }

            $transactionAmount = $transactionShareCount * (float) $transaction->price_per_share;
            $remainingShareCount = $transactionShareCount;

            while ($remainingShareCount > WholeShareBucketEpsilon::VALUE) {
                $roomInBucket = 1.0 - $bucketFill;
                $take = min($remainingShareCount, $roomInBucket);
                $sliceTotalAmount = $transactionAmount * ($take / $transactionShareCount);

                $segment = new WholeShareSegmentResponseData(
                    sourceTransactionId: $transaction->id,
                    externalTransactionId: $transaction->external_transaction_id ?? '',
                    executedAt: $transaction->executed_at->toIso8601String(),
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

                if ($bucketFill >= 1.0 - WholeShareBucketEpsilon::VALUE) {
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
}
