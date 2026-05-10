<?php

namespace App\Console\Commands;

use Domain\Security\Action\SyncSecuritySplitsAction;
use Domain\Security\Data\SyncSecuritySplitsResultData;
use Domain\Security\Data\SyncSecuritySplitsSummaryData;
use Illuminate\Console\Command;

class SyncSecuritySplitsCommand extends Command
{
    protected $signature = 'securities:sync-splits {--ticker=* : Specific ticker(s) to sync}';

    protected $description = 'Fetch stock split history from Yahoo and store it for securities that have transactions (window from earliest transaction through now).';

    public function handle(SyncSecuritySplitsAction $syncSecuritySplits): int
    {
        /** @var array<int, string> $tickers */
        $tickers = collect($this->option('ticker'))
            ->map(static fn (mixed $ticker): string => trim((string) $ticker))
            ->filter(static fn (string $ticker): bool => $ticker !== '')
            ->unique()
            ->values()
            ->all();

        $summary = $syncSecuritySplits->execute($tickers);

        $this->printSummary($summary);
        $this->printErrorMessages($summary);

        return self::SUCCESS;
    }

    private function printSummary(SyncSecuritySplitsSummaryData $summary): void
    {
        $this->info('Security split sync completed.');
        $this->line('Total scanned: '.$summary->totalCount);
        $this->line('Updated: '.$summary->updatedCount);
        $this->line('Skipped: '.$summary->skippedCount);
        $this->line('Failed: '.$summary->failedCount);
    }

    private function printErrorMessages(SyncSecuritySplitsSummaryData $summary): void
    {
        $resultsWithError = collect($summary->results)->filter(
            static fn (SyncSecuritySplitsResultData $result): bool => $result->hasFailed
        );

        if ($resultsWithError->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->warn('Failures:');

        $resultsWithError->each(function (SyncSecuritySplitsResultData $result): void {
            $this->line(sprintf(
                '- %s (security_id=%d): %s',
                $result->ticker,
                $result->securityId,
                $result->failureMessage ?? 'Unknown error'
            ));
        });
    }
}
