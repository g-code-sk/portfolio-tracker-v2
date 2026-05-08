<?php

namespace App\Console\Commands;

use Domain\Security\Action\SyncCurrentSecurityPricesAction;
use Domain\Security\Data\SyncCurrentSecurityPriceResultData;
use Domain\Security\Data\SyncCurrentSecurityPricesSummaryData;
use Illuminate\Console\Command;

class SyncCurrentSecurityPricesCommand extends Command
{
    protected $signature = 'securities:sync-current-prices {--ticker=* : Specific ticker(s) to sync} {--force : Refresh all scanned prices regardless of last update time}';

    protected $description = 'Fetch and store current prices (Finnhub/Yahoo). Without --force, skips symbols updated within SECURITY_PRICE_REFRESH_AFTER_HOURS.';

    public function handle(SyncCurrentSecurityPricesAction $syncCurrentSecurityPrices): int
    {
        /** @var array<int, string> $tickers */
        $tickers = collect($this->option('ticker'))
            ->map(static fn (mixed $ticker): string => trim((string) $ticker))
            ->filter(static fn (string $ticker): bool => $ticker !== '')
            ->unique()
            ->values()
            ->all();

        $summary = $syncCurrentSecurityPrices->execute($tickers, (bool) $this->option('force'));

        $this->printSummary($summary);
        $this->printErrorMessages($summary);

        return self::SUCCESS;
    }

    private function printSummary(SyncCurrentSecurityPricesSummaryData $summary): void
    {
        $this->info('Current security price sync completed.');
        $this->line('Total scanned: '.$summary->totalCount);
        $this->line('Updated: '.$summary->updatedCount);
        $this->line('Skipped: '.$summary->skippedCount);
        $this->line('Failed: '.$summary->failedCount);
    }

    private function printErrorMessages(SyncCurrentSecurityPricesSummaryData $summary): void
    {
        $resultsWithError = collect($summary->results)->filter(
            static fn (SyncCurrentSecurityPriceResultData $result): bool => $result->hasFailed
        );

        if ($resultsWithError->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->warn('Failures:');

        $resultsWithError->each(function (SyncCurrentSecurityPriceResultData $result): void {
            $this->line(sprintf(
                '- %s (security_id=%d): %s',
                $result->ticker,
                $result->securityId,
                $result->failureMessage ?? 'Unknown error'
            ));
        });
    }
}
