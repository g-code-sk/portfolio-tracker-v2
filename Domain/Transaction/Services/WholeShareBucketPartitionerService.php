<?php

namespace Domain\Transaction\Services;

use App\Models\Transaction;
use Domain\Transaction\Data\WholeShareBucketResponseData;
use Domain\Transaction\Data\WholeShareSegmentResponseData;
use Domain\Transaction\WholeShareBucketEpsilon;
use Illuminate\Support\Collection;

final class WholeShareBucketPartitionerService
{
    /** @var array<int, list<WholeShareSegmentResponseData>> */
    private array $bucketSegments = [];

    private int $bucketIndex = 0;

    /** Share quantity already placed toward filling the current whole-share bucket (0..1). */
    private float $bucketFill = 0.0;

    private function __construct() {}

    /**
     * Split transactions into ordered whole-share buckets (each bucket totals one share).
     *
     * @param  Collection<int, Transaction>  $transactionsAscending
     * @return array<int, WholeShareBucketResponseData>
     */
    public static function splitToBuckets(Collection $transactionsAscending): array
    {
        $splitter = new self;

        foreach ($transactionsAscending as $transaction) {
            $splitter->processTransaction($transaction);
        }

        return $splitter->buildBucketResponseData();
    }

    private function processTransaction(Transaction $transaction): void
    {
        if ($this->shouldSkipTransaction($transaction)) {
            return;
        }

        $transactionShareCount = (float) $transaction->number_of_shares;
        $transactionAmount = $transactionShareCount * (float) $transaction->price_per_share;
        $remainingShareCount = $transactionShareCount;

        while ($remainingShareCount > WholeShareBucketEpsilon::VALUE) {
            $remainingShareCount = $this->allocateNextSlice(
                $transaction,
                $transactionShareCount,
                $transactionAmount,
                $remainingShareCount,
            );
        }
    }

    private function shouldSkipTransaction(Transaction $transaction): bool
    {
        return (float) $transaction->number_of_shares <= WholeShareBucketEpsilon::VALUE;
    }

    private function allocateNextSlice(
        Transaction $transaction,
        float $transactionShareCount,
        float $transactionAmount,
        float $remainingShareCount,
    ): float {
        $segmentShareCount = min($remainingShareCount, $this->roomRemainingInCurrentBucket());
        $segmentTotalAmount = $transactionAmount * ($segmentShareCount / $transactionShareCount);

        $segment = WholeShareSegmentResponseData::fromTransactionShareSlice(
            $transaction,
            $segmentShareCount,
            $segmentTotalAmount
        );

        $this->appendSegmentToCurrentBucket($segment);

        $this->bucketFill += $segmentShareCount;
        $remainingShareCount -= $segmentShareCount;

        $this->advanceToNextBucketIfCurrentIsFull();

        return $remainingShareCount;
    }

    private function roomRemainingInCurrentBucket(): float
    {
        return 1.0 - $this->bucketFill;
    }

    private function appendSegmentToCurrentBucket(WholeShareSegmentResponseData $segment): void
    {
        $this->bucketSegments[$this->bucketIndex] ??= [];
        $this->bucketSegments[$this->bucketIndex][] = $segment;
    }

    private function advanceToNextBucketIfCurrentIsFull(): void
    {
        if ($this->bucketFill < 1.0 - WholeShareBucketEpsilon::VALUE) {
            return;
        }

        $this->bucketFill = 0.0;
        $this->bucketIndex++;
    }

    /**
     * @return array<int, WholeShareBucketResponseData>
     */
    private function buildBucketResponseData(): array
    {
        ksort($this->bucketSegments);

        $buckets = [];

        foreach ($this->bucketSegments as $index => $segments) {
            $buckets[(int) $index] = new WholeShareBucketResponseData(
                wholeShareBucketIndex: (int) $index,
                segments: $segments,
            );
        }

        return $buckets;
    }
}
