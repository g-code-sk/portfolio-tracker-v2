<?php

namespace App\Console\Commands;

use Domain\Security\Action\SyncCurrentSecurityPricesAction;
use Domain\Security\Data\SyncCurrentSecurityPriceResultData;
use Domain\Security\Data\SyncCurrentSecurityPricesSummaryData;
use Illuminate\Console\Command;

class SyncCurrentSecurityPricesCommand extends Command
{
    protected $signature = 'securities:sync-current-prices {--ticker=* : Specific ticker(s) to sync}';

    protected $description = 'Fetch and store current prices for securities from Yahoo';

    public function handle(SyncCurrentSecurityPricesAction $syncCurrentSecurityPrices): int
    {
        /** @var array<int, string> $tickers */
        $tickers = collect($this->option('ticker'))
            ->map(static fn (mixed $ticker): string => trim((string) $ticker))
            ->filter(static fn (string $ticker): bool => $ticker !== '')
            ->unique()
            ->values()
            ->all();

        $summary = $syncCurrentSecurityPrices->execute($tickers);

        $this->printSummary($summary);
        $this->printFailedResults($summary);

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

    private function printFailedResults(SyncCurrentSecurityPricesSummaryData $summary): void
    {
        $failedResults = collect($summary->results)->filter(
            static fn (SyncCurrentSecurityPriceResultData $result): bool => $result->hasFailed
        );

        if ($failedResults->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->warn('Failures:');

        $failedResults->each(function (SyncCurrentSecurityPriceResultData $result): void {
            $this->line(sprintf(
                '- %s (security_id=%d): %s',
                $result->ticker,
                $result->securityId,
                $result->failureMessage ?? 'Unknown error'
            ));
        });
    }
}
