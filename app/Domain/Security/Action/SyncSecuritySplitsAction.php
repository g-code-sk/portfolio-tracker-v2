<?php

namespace Domain\Security\Action;

use App\Models\Security;
use App\Models\SecurityDataProvider;
use App\Models\SecuritySplit;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Domain\Security\Contract\SplitHistoryProviderInterface;
use Domain\Security\Data\SecuritySplitEventData;
use Domain\Security\Data\SyncSecuritySplitsResultData;
use Domain\Security\Data\SyncSecuritySplitsSummaryData;
use Domain\Security\Enums\SecurityDataProviderCode;
use RuntimeException;

class SyncSecuritySplitsAction
{
    public function __construct(
        private readonly SplitHistoryProviderInterface $splitHistoryProvider,
    ) {}

    /**
     * @param  list<string>  $tickers
     */
    public function execute(array $tickers = []): SyncSecuritySplitsSummaryData
    {
        $yahooProviderId = SecurityDataProvider::query()
            ->where('code', SecurityDataProviderCode::Yahoo->value)
            ->value('id');

        if ($yahooProviderId === null) {
            throw new RuntimeException(sprintf(
                'Missing security_data_providers row with code "%s". Run seeders.',
                SecurityDataProviderCode::Yahoo->value,
            ));
        }

        $securityQuery = Security::query()
            ->whereTickerPresent()
            ->orderBy('ticker');

        if ($tickers !== []) {
            $securityQuery->whereIn('ticker', $tickers);
        }

        $securities = $securityQuery->get();

        $results = $securities->map(function (Security $security) use ($yahooProviderId): SyncSecuritySplitsResultData {
            $minExecutedAt = Transaction::query()
                ->where('security_id', $security->id)
                ->min('executed_at');

            if ($minExecutedAt === null) {
                return SyncSecuritySplitsResultData::skipped(
                    $security->id,
                    $security->ticker,
                    'No transactions for this security',
                );
            }

            $start = CarbonImmutable::parse($minExecutedAt)->utc()->startOfDay();
            $end = CarbonImmutable::now('UTC');

            $events = $this->splitHistoryProvider->fetchSplitEvents($security->ticker, $start, $end);

            if ($events === null) {
                return SyncSecuritySplitsResultData::failed(
                    $security->id,
                    $security->ticker,
                    'Failed to fetch split history from Yahoo',
                );
            }

            $synced = 0;

            foreach ($events as $event) {
                $this->persistSplitEvent($security->id, $yahooProviderId, $event);
                $synced++;
            }

            return SyncSecuritySplitsResultData::updated($security->id, $security->ticker, $synced);
        })->all();

        $updatedCount = collect($results)->where('isUpdated', true)->count();
        $skippedCount = collect($results)->where('isSkipped', true)->count();
        $failedCount = collect($results)->where('hasFailed', true)->count();

        return new SyncSecuritySplitsSummaryData(
            totalCount: count($results),
            updatedCount: $updatedCount,
            skippedCount: $skippedCount,
            failedCount: $failedCount,
            results: $results,
        );
    }

    private function persistSplitEvent(int $securityId, int $yahooProviderId, SecuritySplitEventData $event): void
    {
        SecuritySplit::query()->updateOrCreate(
            [
                'security_id' => $securityId,
                'security_data_provider_id' => $yahooProviderId,
                'effective_on' => $event->effectiveOn->toDateString(),
            ],
            [
                'ratio_numerator' => $event->ratioNumerator,
                'ratio_denominator' => $event->ratioDenominator,
                'raw_ratio' => $event->rawRatio,
            ],
        );
    }
}
