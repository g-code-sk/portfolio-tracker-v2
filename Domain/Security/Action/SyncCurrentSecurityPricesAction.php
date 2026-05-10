<?php

namespace Domain\Security\Action;

use App\Models\Security;
use App\Support\ApplicationConfig;
use Domain\Security\Data\SyncCurrentSecurityPriceResultData;
use Domain\Security\Data\SyncCurrentSecurityPricesSummaryData;
use Illuminate\Support\Carbon;

class SyncCurrentSecurityPricesAction
{
    public function __construct(
        private readonly SyncCurrentSecurityPriceAction $syncCurrentSecurityPrice,
        private readonly ApplicationConfig $applicationConfig,
    ) {}

    /**
     * @param  list<string>  $tickers
     */
    public function execute(array $tickers = [], bool $forceSync = false): SyncCurrentSecurityPricesSummaryData
    {
        $securityQuery = Security::query()
            ->whereTickerPresent()
            ->orderBy('ticker');

        if ($tickers !== []) {
            $securityQuery->whereIn('ticker', $tickers);
        }

        $securities = $securityQuery->get();

        $ttlHours = $this->applicationConfig->getSecurityPriceRefreshAfterHours();

        $results = $securities->map(function (Security $security) use ($forceSync, $ttlHours): SyncCurrentSecurityPriceResultData {
            $now = Carbon::now();

            $wasNotUpdatedRecently = $security->isCurrentPriceStale($now, $ttlHours);

            if ($forceSync || $wasNotUpdatedRecently) {
                return $this->syncCurrentSecurityPrice->execute($security);
            }

            return SyncCurrentSecurityPriceResultData::skipped(
                $security->id,
                $security->ticker,
                sprintf('Price refreshed within the last %d hour(s)', $ttlHours),
            );
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
