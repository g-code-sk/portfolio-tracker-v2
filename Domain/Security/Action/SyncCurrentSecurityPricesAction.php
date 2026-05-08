<?php

namespace Domain\Security\Action;

use App\Models\Security;
use Domain\Security\Data\SyncCurrentSecurityPriceResultData;
use Domain\Security\Data\SyncCurrentSecurityPricesSummaryData;

class SyncCurrentSecurityPricesAction
{
    public function __construct(
        private readonly SyncCurrentSecurityPriceAction $syncCurrentSecurityPrice
    ) {}

    /**
     * @param list<string> $tickers
     */
    public function execute(array $tickers = []): SyncCurrentSecurityPricesSummaryData
    {
        $securityQuery = Security::query()
            ->whereTickerPresent()
            ->orderBy('ticker');

        if ($tickers !== []) {
            $securityQuery->whereIn('ticker', $tickers);
        }

        $securities = $securityQuery->get();

        $results = $securities->map(function (Security $security): SyncCurrentSecurityPriceResultData {
            return $this->syncCurrentSecurityPrice->execute($security);
        })->all();

        $updatedCount = collect($results)->where('isUpdated', true)->count();
        $skippedCount = collect($results)->where('isSkipped', true)->count();
        $failedCount = collect($results)->where('hasFailed', true)->count();

        return new SyncCurrentSecurityPricesSummaryData(
            totalCount: count($results),
            updatedCount: $updatedCount,
            skippedCount: $skippedCount,
            failedCount: $failedCount,
            results: $results,
        );
    }
}
