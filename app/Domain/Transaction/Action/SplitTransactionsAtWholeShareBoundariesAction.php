<?php

namespace Domain\Transaction\Action;

use App\Models\SecuritySplit;
use App\Models\Transaction;
use App\Services\PortfolioSecuritySplitAdjustmentService;
use Domain\Transaction\Data\SplitAdjustedTransaction;
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
     * @param  Collection<int, SecuritySplit>  $splitsForSecurity
     */
    public function execute(
        Collection $transactionsAscending,
        Collection $splitsForSecurity,
        PortfolioSecuritySplitAdjustmentService $splitAdjustmentService,
        ?string $portfolioName = null,
    ): WholeShareGroupsResponseData {
        $groups = [];
        $globalGroupIndex = 0;

        $rows = $splitAdjustmentService->collectSplitAdjustedTransactions($transactionsAscending, $splitsForSecurity);

        $cycles = TransactionPositionCyclePartitionerService::partition($rows);

        foreach ($cycles as $cycleRows) {
            $buyRows = $cycleRows
                ->filter(fn (SplitAdjustedTransaction $row): bool => $row->transaction->type->code === TransactionTypeCode::Buy)
                ->values();

            $sellRows = $cycleRows
                ->filter(fn (SplitAdjustedTransaction $row): bool => $row->transaction->type->code === TransactionTypeCode::Sell)
                ->values();

            $buyBuckets = WholeShareBucketPartitionerService::splitToBuckets($buyRows);
            $sellBuckets = WholeShareBucketPartitionerService::splitToBuckets($sellRows);

            /** @var array<int, int> $groupIndices */
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

        return WholeShareGroupsResponseData::fromGroups($groups, $portfolioName);
    }
}
