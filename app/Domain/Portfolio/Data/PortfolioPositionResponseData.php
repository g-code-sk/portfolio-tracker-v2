<?php

namespace Domain\Portfolio\Data;

use App\Models\Transaction;
use App\Services\PortfolioSecuritySplitAdjustmentService;
use App\Services\SplitAdjustedTransactionAmounts;
use Domain\Transaction\Enums\TransactionTypeCode;
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
     * @param  Collection<int, SecuritySplit>  $splitsForSecurity
     */
    public static function fromTransactions(Collection $transactions, Collection $splitsForSecurity): self
    {
        $splitAdjustmentService = new PortfolioSecuritySplitAdjustmentService;

        $adjustedBuyTransactionsData = $transactions
            ->filter(fn (Transaction $transaction): bool => $transaction->type->code === TransactionTypeCode::Buy)
            ->map(function (Transaction $transaction) use ($splitsForSecurity, $splitAdjustmentService): SplitAdjustedTransactionAmounts {
                return $splitAdjustmentService->adjust($transaction, $splitsForSecurity);
            });

        $adjustedSellTransactionsData = $transactions
            ->filter(fn (Transaction $transaction): bool => $transaction->type->code === TransactionTypeCode::Sell)
            ->map(function (Transaction $transaction) use ($splitsForSecurity, $splitAdjustmentService): SplitAdjustedTransactionAmounts {
                return $splitAdjustmentService->adjust($transaction, $splitsForSecurity);
            });

        $sharesBought = collect($adjustedBuyTransactionsData)
            ->sum(fn (SplitAdjustedTransactionAmounts $adjustedTransactionData): float => $adjustedTransactionData->numberOfShares);

        $sharesSold = collect($adjustedSellTransactionsData)
            ->sum(fn (SplitAdjustedTransactionAmounts $adjustedTransactionData): float => $adjustedTransactionData->numberOfShares);

        $investedAmount = collect($adjustedBuyTransactionsData)
            ->sum(fn (SplitAdjustedTransactionAmounts $adjustedTransactionData): float => $adjustedTransactionData->numberOfShares * $adjustedTransactionData->pricePerShare);

        $soldAmount = collect($adjustedSellTransactionsData)
            ->sum(fn (SplitAdjustedTransactionAmounts $adjustedTransactionData): float => $adjustedTransactionData->numberOfShares * $adjustedTransactionData->pricePerShare);

        $totalShares = $sharesBought - $sharesSold;

        $transactionForMetadata = $transactions->first();

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
            totalGainLossAmount: self::resolveTotalGainLossAmount($transactionForMetadata->currency->symbol, $investedAmount, $soldAmount, $totalShares, $transactionForMetadata->security->current_price, $transactionForMetadata->security->current_price_currency),
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
