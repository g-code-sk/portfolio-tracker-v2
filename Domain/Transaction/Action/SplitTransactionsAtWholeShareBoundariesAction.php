<?php

namespace Domain\Transaction\Action;

use App\Models\Transaction;
use Domain\Transaction\Data\WholeShareGroupResponseData;
use Domain\Transaction\Data\WholeShareGroupsResponseData;
use Domain\Transaction\Enums\TransactionTypeCode;
use Domain\Transaction\Services\TransactionPositionCyclePartitionerService;
use Domain\Transaction\Services\WholeShareBucketPartitionerService;
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

            $buyBuckets = WholeShareBucketPartitionerService::splitToBuckets($buyTransactions);
            $sellBuckets = WholeShareBucketPartitionerService::splitToBuckets($sellTransactions);

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
}
