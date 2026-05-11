<?php

namespace Domain\Transaction\Services;

use Domain\Transaction\Data\SplitAdjustedTransaction;
use Domain\Transaction\Enums\TransactionTypeCode;
use Domain\Transaction\WholeShareBucketEpsilon;
use Illuminate\Support\Collection;

final class TransactionPositionCyclePartitionerService
{
    /** @var list<Collection<int, SplitAdjustedTransaction>> */
    private array $completedCycles = [];

    //  Running shares is the sum of the shares in the current cycle.
    private float $runningShares = 0.0;

    /** @var Collection<int, SplitAdjustedTransaction> */
    private Collection $currentCycleTransactions;

    private function __construct()
    {
        $this->currentCycleTransactions = collect();
    }

    /**
     * Partition the transactions into position cycles - a cycle means
     * that the position was opened and closed within the same cycle (all shares sold).
     *
     * @param  Collection<int, SplitAdjustedTransaction>  $splitAdjustedTransactionsAscending
     * @return list<Collection<int, SplitAdjustedTransaction>>
     */
    public static function splitToCycles(Collection $splitAdjustedTransactionsAscending): array
    {
        $partitioner = new self;

        foreach ($splitAdjustedTransactionsAscending as $row) {
            $partitioner->processRow($row);
        }

        $partitioner->flushIncompleteCycle();

        return $partitioner->completedCycles;
    }

    private function processRow(SplitAdjustedTransaction $row): void
    {
        if ($this->shouldSkipRow($row)) {
            return;
        }

        $shares = $row->numberOfShares;

        if ($row->transaction->type->code === TransactionTypeCode::Buy) {
            $this->applyBuy($row, $shares);

            return;
        }

        if ($row->transaction->type->code === TransactionTypeCode::Sell) {
            $this->applySell($row, $shares);
        }
    }

    private function shouldSkipRow(SplitAdjustedTransaction $row): bool
    {
        return $row->numberOfShares <= WholeShareBucketEpsilon::VALUE;
    }

    private function applyBuy(SplitAdjustedTransaction $row, float $shares): void
    {
        $this->startNewCycleIfPositionWasClosedButBufferNotEmpty();

        $this->currentCycleTransactions->push($row);
        $this->runningShares += $shares;
    }

    private function startNewCycleIfPositionWasClosedButBufferNotEmpty(): void
    {
        if (! $this->isApproximatelyZero($this->runningShares)) {
            return;
        }

        if ($this->currentCycleTransactions->isEmpty()) {
            return;
        }

        $this->completedCycles[] = $this->currentCycleTransactions->values();
        $this->currentCycleTransactions = collect();
    }

    private function applySell(SplitAdjustedTransaction $row, float $shares): void
    {
        $this->currentCycleTransactions->push($row);
        $this->runningShares -= $shares;

        if (! $this->isApproximatelyZero($this->runningShares)) {
            return;
        }

        $this->runningShares = 0.0;

        $this->completedCycles[] = $this->currentCycleTransactions->values();
        $this->currentCycleTransactions = collect();
    }

    private function flushIncompleteCycle(): void
    {
        if ($this->currentCycleTransactions->isEmpty()) {
            return;
        }

        $this->completedCycles[] = $this->currentCycleTransactions->values();
    }

    private function isApproximatelyZero(float $value): bool
    {
        return abs($value) <= WholeShareBucketEpsilon::VALUE;
    }
}
