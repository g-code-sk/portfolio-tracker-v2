<?php

namespace Domain\Transaction\Services;

use App\Models\Transaction;
use Domain\Transaction\Enums\TransactionTypeCode;
use Domain\Transaction\WholeShareBucketEpsilon;
use Illuminate\Support\Collection;

final class TransactionPositionCyclePartitionerService
{
    /** @var list<Collection<int, Transaction>> */
    private array $completedCycles = [];

    //  Running shares is the sum of the shares in the current cycle.
    private float $runningShares = 0.0;

    /** @var Collection<int, Transaction> */
    private Collection $currentCycleTransactions;

    private function __construct()
    {
        $this->currentCycleTransactions = collect();
    }

    /**
     * Partition the transactions into position cycles - a cycle means
     * that the position was opened and closed within the same cycle (all shares sold).
     *
     * @param  Collection<int, Transaction>  $transactionsAscending
     * @return list<Collection<int, Transaction>>
     */
    public static function partition(Collection $transactionsAscending): array
    {
        $partitioner = new self;

        foreach ($transactionsAscending as $transaction) {
            $partitioner->processTransaction($transaction);
        }

        $partitioner->flushIncompleteCycle();

        return $partitioner->completedCycles;
    }

    private function processTransaction(Transaction $transaction): void
    {
        if ($this->shouldSkipTransaction($transaction)) {
            return;
        }

        $shares = (float) $transaction->number_of_shares;

        if ($transaction->type->code === TransactionTypeCode::Buy) {
            $this->applyBuy($transaction, $shares);

            return;
        }

        if ($transaction->type->code === TransactionTypeCode::Sell) {
            $this->applySell($transaction, $shares);
        }
    }

    private function shouldSkipTransaction(Transaction $transaction): bool
    {
        return (float) $transaction->number_of_shares <= WholeShareBucketEpsilon::VALUE;
    }

    private function applyBuy(Transaction $transaction, float $shares): void
    {
        $this->startNewCycleIfPositionWasClosedButBufferNotEmpty();

        $this->currentCycleTransactions->push($transaction);
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

    private function applySell(Transaction $transaction, float $shares): void
    {
        $this->currentCycleTransactions->push($transaction);
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
