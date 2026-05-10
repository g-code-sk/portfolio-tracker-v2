<?php

namespace Domain\Security\Data;

final readonly class SyncCurrentSecurityPricesSummaryData
{
    /**
     * @param array<int, SyncCurrentSecurityPriceResultData> $results
     */
    public function __construct(
        public int $totalCount,
        public int $updatedCount,
        public int $skippedCount,
        public int $failedCount,
        public array $results,
    ) {}
}
