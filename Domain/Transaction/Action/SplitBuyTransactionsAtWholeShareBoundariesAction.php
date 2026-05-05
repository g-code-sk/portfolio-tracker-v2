<?php

namespace Domain\Transaction\Action;

use App\Models\Transaction;
use Domain\Transaction\Data\WholeShareBuyBucketResponseData;
use Domain\Transaction\Data\WholeShareBuySegmentResponseData;
use Domain\Transaction\Data\WholeShareBuySegmentsResponseData;
use Domain\Transaction\Enums\TransactionTypeCode;
use Illuminate\Support\Collection;

class SplitBuyTransactionsAtWholeShareBoundariesAction
{
    private const float BUCKET_EPSILON = 1e-9;

    /**
     * @param  Collection<int, Transaction>  $transactionsAscending
     */
    public function execute(Collection $transactionsAscending): WholeShareBuySegmentsResponseData
    {
        $buys = $transactionsAscending->filter(function (Transaction $transaction): bool {
            return $transaction->type->code === TransactionTypeCode::Buy;
        })->values();

        if ($buys->isEmpty()) {
            return new WholeShareBuySegmentsResponseData(buckets: []);
        }

        /** @var array<int, list<WholeShareBuySegmentResponseData>> $bucketSegments */
        $bucketSegments = [];
        $bucketIndex = 0;
        $bucketFill = 0.0;

        foreach ($buys as $transaction) {
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

                $segment = new WholeShareBuySegmentResponseData(
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
            $buckets[] = new WholeShareBuyBucketResponseData(
                wholeShareBucketIndex: (int) $index,
                segments: $segments,
            );
        }

        return new WholeShareBuySegmentsResponseData(buckets: $buckets);
    }
}
