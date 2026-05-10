<?php

namespace Domain\Transaction\Action;

use App\Models\Currency;
use App\Models\Portfolio;
use App\Models\Security;
use App\Models\Transaction;
use App\Models\TransactionType;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Domain\Transaction\Data\Trading212ImportRowData;
use Domain\Transaction\Enums\Trading212TransactionType;
use Domain\Transaction\Enums\TransactionTypeCode;
use Domain\Transaction\Exception\InvalidExecutionTimeTrading212ImportException;
use Domain\Transaction\Exception\InvalidPriceCurrencyTrading212ImportException;
use Domain\Transaction\Exception\MissingExternalTransactionIdTrading212ImportException;
use Domain\Transaction\Exception\MissingPriceCurrencyTrading212ImportException;
use Domain\Transaction\Exception\MissingShareOrPriceTrading212ImportException;
use Domain\Transaction\Exception\MissingTickerTrading212ImportException;
use Domain\Transaction\Exception\MissingTransactionTypeTrading212ImportException;
use Domain\Transaction\Exception\NonNumericShareOrPriceTrading212ImportException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ImportTrading212TransactionsAction
{
    public function __construct(
        private readonly ParseTrading212CsvAction $parseTrading212Csv
    ) {}

    public function execute(Portfolio $portfolio, string $storedFilePath): void
    {
        $importRows = $this->parseTrading212Csv->execute($storedFilePath);

        $validActionImportRows = $importRows->filter(function (Trading212ImportRowData $importRow): bool {
            return $importRow->action === Trading212TransactionType::MarketBuy || $importRow->action === Trading212TransactionType::MarketSell;
        });

        $transactionTypesByCode = $this->getTransactionTypesByCode();

        $this->createTransactions($validActionImportRows, $portfolio, $transactionTypesByCode);
    }

    /**
     * @return Collection<string, TransactionType>
     */
    private function getTransactionTypesByCode(): Collection
    {
        return TransactionType::query()
            ->whereIn('code', TransactionTypeCode::values())
            ->get()
            ->keyBy(static fn (TransactionType $transactionType): string => $transactionType->code->value);
    }

    private function validateImportRow(Trading212ImportRowData $importRow): void
    {
        if (! $importRow->hasTicker()) {
            throw MissingTickerTrading212ImportException::fromImportRow($importRow);
        }

        if (! $importRow->hasExternalTransactionId()) {
            throw MissingExternalTransactionIdTrading212ImportException::fromImportRow($importRow);
        }

        if (! $importRow->hasShareAndPriceFields()) {
            throw MissingShareOrPriceTrading212ImportException::fromImportRow($importRow);
        }

        if (! $importRow->hasCurrencyPricePerShare()) {
            throw MissingPriceCurrencyTrading212ImportException::fromImportRow($importRow);
        }

        if (! $importRow->hasThreeLetterPriceCurrency()) {
            throw InvalidPriceCurrencyTrading212ImportException::fromImportRow($importRow);
        }

        if (! $importRow->hasNumericShareAndPrice()) {
            throw NonNumericShareOrPriceTrading212ImportException::fromImportRow($importRow);
        }

        if (! $importRow->hasTime()) {
            throw InvalidExecutionTimeTrading212ImportException::fromImportRow($importRow);
        }
    }

    private function parseExecutedAt(Trading212ImportRowData $importRow): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', $importRow->time);
        } catch (InvalidFormatException) {
            throw InvalidExecutionTimeTrading212ImportException::fromImportRow($importRow);
        }
    }

    private function createTransactions(Collection $importRows, Portfolio $portfolio, Collection $transactionTypesByCode): void
    {
        DB::transaction(function () use ($importRows, $portfolio, $transactionTypesByCode): void {
            $importRows->each(function (Trading212ImportRowData $importRow) use ($portfolio, $transactionTypesByCode): void {
                $this->validateImportRow($importRow);

                $transactionType = $this->getTransactionType($importRow, $transactionTypesByCode);
                $currency = $this->getOrCreateCurrency($importRow->currencyPricePerShare);
                $security = $this->getOrCreateSecurity($importRow);

                Transaction::query()->updateOrCreate(
                    [
                        'portfolio_id' => $portfolio->id,
                        'external_transaction_id' => $importRow->externalTransactionId,
                    ],
                    [
                        'security_id' => $security->id,
                        'type_id' => $transactionType->id,
                        'number_of_shares' => $importRow->numberOfShares,
                        'price_per_share' => $importRow->pricePerShare,
                        'currency_id' => $currency->id,
                        'executed_at' => $this->parseExecutedAt($importRow),
                    ],
                );
            });
        });
    }

    private function getTransactionType(Trading212ImportRowData $importRow, Collection $transactionTypesByCode): TransactionType
    {
        $transactionTypeCode = match ($importRow->action) {
            Trading212TransactionType::MarketBuy => TransactionTypeCode::Buy,
            Trading212TransactionType::MarketSell => TransactionTypeCode::Sell,
        };

        $transactionType = $transactionTypesByCode->get($transactionTypeCode->value);

        if ($transactionType === null) {
            throw MissingTransactionTypeTrading212ImportException::fromImportRow($importRow);
        }

        return $transactionType;
    }

    private function getOrCreateCurrency(string $currencySymbol): Currency
    {
        return Currency::query()->firstOrCreate(
            ['symbol' => $currencySymbol],
            [
                'name' => $currencySymbol,
            ],
        );
    }

    private function getOrCreateSecurity(Trading212ImportRowData $importRow): Security
    {
        $securityName = $importRow->name ?? $importRow->ticker;

        $security = Security::query()->firstOrCreate(
            ['ticker' => $importRow->ticker],
            [
                'name' => $securityName,
                'isin' => $importRow->isin,
            ],
        );

        if ($security->hasPlaceholderNameComparedToTicker($importRow->ticker)) {
            $security->name = $securityName;
        }

        if ($security->isin === null && $importRow->hasIsin()) {
            $security->isin = $importRow->isin;
        }

        if ($security->isDirty()) {
            $security->save();
        }

        return $security;
    }
}
