<?php

namespace Domain\Transaction\Action;

use App\Enums\SecurityTypeCode;
use App\Models\Currency;
use App\Models\Portfolio;
use App\Models\Security;
use App\Models\SecurityType;
use App\Models\Transaction;
use Domain\Transaction\Data\Trading212ImportRowData;
use Domain\Transaction\Enums\Trading212TransactionType;
use Domain\Transaction\Exception\InvalidPriceCurrencyTrading212ImportException;
use Domain\Transaction\Exception\MissingExternalTransactionIdTrading212ImportException;
use Domain\Transaction\Exception\MissingPriceCurrencyTrading212ImportException;
use Domain\Transaction\Exception\MissingSecurityTypeTrading212ImportException;
use Domain\Transaction\Exception\MissingShareOrPriceTrading212ImportException;
use Domain\Transaction\Exception\MissingTickerTrading212ImportException;
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

		$securityTypesByCode = $this->getSecurityTypesByCode();

		$this->createTransactions($validActionImportRows, $portfolio, $securityTypesByCode);
	}

	/**
	 * @return Collection<string, SecurityType>
	 */
	private function getSecurityTypesByCode(): Collection
	{
		return SecurityType::query()
			->whereIn('code', SecurityTypeCode::values())
			->get()
			->keyBy(static fn(SecurityType $securityType): string => $securityType->code->value);
	}

	private function validateImportRow(Trading212ImportRowData $importRow): void
	{
		if ($importRow->ticker === null) {
			throw MissingTickerTrading212ImportException::fromImportRow($importRow);
		}

		if ($importRow->externalTransactionId === null) {
			throw MissingExternalTransactionIdTrading212ImportException::fromImportRow($importRow);
		}

		if ($importRow->numberOfShares === null || $importRow->pricePerShare === null) {
			throw MissingShareOrPriceTrading212ImportException::fromImportRow($importRow);
		}

		if ($importRow->currencyPricePerShare === null) {
			throw MissingPriceCurrencyTrading212ImportException::fromImportRow($importRow);
		}

		if (strlen($importRow->currencyPricePerShare) !== 3) {
			throw InvalidPriceCurrencyTrading212ImportException::fromImportRow($importRow);
		}

		if (! is_numeric($importRow->numberOfShares) || ! is_numeric($importRow->pricePerShare)) {
			throw NonNumericShareOrPriceTrading212ImportException::fromImportRow($importRow);
		}
	}

	private function createTransactions(Collection $importRows, Portfolio $portfolio, Collection $securityTypesByCode): void
	{
		DB::transaction(function () use ($importRows, $portfolio, $securityTypesByCode): void {
			$importRows->each(function (Trading212ImportRowData $importRow) use ($portfolio, $securityTypesByCode): void {
				$this->validateImportRow($importRow);

				$securityType = $this->getSecurityType($importRow, $securityTypesByCode);
				$currency = $this->getOrCreateCurrency($importRow->currencyPricePerShare);
				$security = $this->getOrCreateSecurity($importRow);

				Transaction::query()->updateOrCreate(
					[
						'portfolio_id' => $portfolio->id,
						'external_transaction_id' => $importRow->externalTransactionId,
					],
					[
						'security_id' => $security->id,
						'type_id' => $securityType->id,
						'number_of_shares' => $importRow->numberOfShares,
						'price_per_share' => $importRow->pricePerShare,
						'currency_id' => $currency->id,
					],
				);
			});
		});
	}

	private function getSecurityType(Trading212ImportRowData $importRow, Collection $securityTypesByCode): SecurityType
	{
		$securityTypeCode = match ($importRow->action) {
			Trading212TransactionType::MarketBuy => SecurityTypeCode::Buy,
			Trading212TransactionType::MarketSell => SecurityTypeCode::Sell,
		};

		$securityType = $securityTypesByCode->get($securityTypeCode->value);

		if ($securityType === null) {
			throw MissingSecurityTypeTrading212ImportException::fromImportRow($importRow);
		}

		return $securityType;
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

		if ($security->name === '' || $security->name === $importRow->ticker) {
			$security->name = $securityName;
		}

		if ($security->isin === null && $importRow->isin !== null) {
			$security->isin = $importRow->isin;
		}

		if ($security->isDirty()) {
			$security->save();
		}

		return $security;
	}
}
