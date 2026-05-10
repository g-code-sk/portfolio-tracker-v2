<?php

namespace Domain\Portfolio\Data;

use App\Models\SecuritySplit;
use App\Models\Transaction;
use App\Services\PortfolioSecuritySplitAdjustmentService;
use Domain\Transaction\Data\SplitAdjustedBuySellTotals;
use Domain\Transaction\Data\SplitAdjustedTransaction;
use Domain\Transaction\WholeShareBucketEpsilon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PortfolioPositionResponseData extends Data
{
    public function __construct(
        public int $securityId,
        public int $currencyId,
        public string $ticker,
        public string $name,
        public string $currencySymbol,
        public float $sharesBought,
        public float $sharesSold,
        public float $investedAmount,
        public float $soldAmount,
        public float $totalShares,
        public ?float $currentPrice,
        public ?string $currentPriceCurrency,
        public ?float $totalGainLossAmount,
        public ?float $totalReturnPercent,
    ) {}

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @param  Collection<int, SecuritySplit>  $splits
     */
    public static function fromTransactions(
        Collection $transactions,
        Collection $splits,
        PortfolioSecuritySplitAdjustmentService $splitAdjustmentService,
    ): self {
        $splitAdjustedTransactions = $transactions->map(function (Transaction $transaction) use ($splitAdjustmentService, $splits): SplitAdjustedTransaction {
            return SplitAdjustedTransaction::from(
                $transaction,
                $splitAdjustmentService->adjust($transaction, $splits),
            );
        });

        return self::fromSplitAdjustedTransactions($splitAdjustedTransactions);
    }

    /**
     * @param  Collection<int, SplitAdjustedTransaction>  $splitAdjustedTransactions
     */
    private static function fromSplitAdjustedTransactions(Collection $splitAdjustedTransactions): self
    {
        $totals = SplitAdjustedBuySellTotals::fromSplitAdjustedTransactions($splitAdjustedTransactions);

        $sharesBought = $totals->sharesBought;
        $sharesSold = $totals->sharesSold;
        $investedAmount = $totals->investedAmount;
        $soldAmount = $totals->soldAmount;
        $totalShares = $totals->totalShares();

        $transactionForMetadata = $splitAdjustedTransactions->first()->transaction;

        $totalGainLossAmount = self::resolveTotalGainLossAmount(
            $transactionForMetadata->currency->symbol,
            $investedAmount,
            $soldAmount,
            $totalShares,
            $transactionForMetadata->security->current_price,
            $transactionForMetadata->security->current_price_currency
        );

        return new PortfolioPositionResponseData(
            securityId: $transactionForMetadata->security_id,
            currencyId: $transactionForMetadata->currency_id,
            ticker: $transactionForMetadata->security->ticker,
            name: $transactionForMetadata->security->name,
            currencySymbol: $transactionForMetadata->currency->symbol,
            currentPrice: $transactionForMetadata->security->current_price,
            currentPriceCurrency: $transactionForMetadata->security->current_price_currency,
            sharesBought: $sharesBought,
            sharesSold: $sharesSold,
            investedAmount: $investedAmount,
            soldAmount: $soldAmount,
            totalShares: $totalShares,
            totalGainLossAmount: $totalGainLossAmount,
            totalReturnPercent: self::resolveTotalReturnPercent($totalGainLossAmount, $investedAmount),
        );
    }

    /**
     * Build position metrics from pre-aggregated totals (used by unit tests and reporting-style call sites).
     *
     * @param  array<string, mixed>  $overrides  Named constructor fields including numeric aggregates.
     */
    public static function fromAggregatedPresentation(array $overrides = []): self
    {
        $defaults = [
            'securityId' => 1,
            'currencyId' => 1,
            'ticker' => 'TST',
            'name' => 'Test Security',
            'currencySymbol' => 'USD',
            'sharesBought' => 10.0,
            'sharesSold' => 10.0,
            'investedAmount' => 1000.0,
            'soldAmount' => 1200.0,
            'currentPrice' => 150.0,
            'currentPriceCurrency' => 'USD',
        ];

        $fields = array_merge($defaults, $overrides);

        $sharesBought = (float) $fields['sharesBought'];
        $sharesSold = (float) $fields['sharesSold'];
        $investedAmount = (float) $fields['investedAmount'];
        $soldAmount = (float) $fields['soldAmount'];
        $totalShares = array_key_exists('totalShares', $fields)
            ? (float) $fields['totalShares']
            : $sharesBought - $sharesSold;

        $currencySymbol = (string) $fields['currencySymbol'];
        $currentPrice = array_key_exists('currentPrice', $fields) ? $fields['currentPrice'] : null;
        $currentPriceCurrency = array_key_exists('currentPriceCurrency', $fields) ? $fields['currentPriceCurrency'] : null;

        $totalGainLossAmount = self::resolveTotalGainLossAmount(
            $currencySymbol,
            $investedAmount,
            $soldAmount,
            $totalShares,
            $currentPrice !== null ? (float) $currentPrice : null,
            $currentPriceCurrency !== null ? (string) $currentPriceCurrency : null,
        );

        return new self(
            securityId: (int) $fields['securityId'],
            currencyId: (int) $fields['currencyId'],
            ticker: (string) $fields['ticker'],
            name: (string) $fields['name'],
            currencySymbol: $currencySymbol,
            sharesBought: $sharesBought,
            sharesSold: $sharesSold,
            investedAmount: $investedAmount,
            soldAmount: $soldAmount,
            totalShares: $totalShares,
            currentPrice: $currentPrice !== null ? (float) $currentPrice : null,
            currentPriceCurrency: $currentPriceCurrency !== null ? (string) $currentPriceCurrency : null,
            totalGainLossAmount: $totalGainLossAmount,
            totalReturnPercent: self::resolveTotalReturnPercent($totalGainLossAmount, $investedAmount),
        );
    }

    private static function resolveTotalGainLossAmount(
        string $currencySymbol,
        float $investedAmount,
        float $soldAmount,
        float $totalShares,
        ?float $currentPrice,
        ?string $currentPriceCurrency,
    ): ?float {
        if ($investedAmount <= 0.0) {
            return null;
        }

        if (abs($totalShares) < WholeShareBucketEpsilon::VALUE) {
            return $soldAmount - $investedAmount;
        }

        if ($currentPrice === null) {
            return null;
        }

        if ($currentPriceCurrency === null || $currentPriceCurrency === '') {
            return null;
        }

        if ($currencySymbol !== $currentPriceCurrency) {
            return null;
        }

        return $soldAmount + ($totalShares * $currentPrice) - $investedAmount;
    }

    private static function resolveTotalReturnPercent(
        ?float $totalGainLossAmount,
        float $investedAmount,
    ): ?float {
        if ($totalGainLossAmount === null || $investedAmount <= 0.0) {
            return null;
        }

        return ($totalGainLossAmount / $investedAmount) * 100;
    }
}
