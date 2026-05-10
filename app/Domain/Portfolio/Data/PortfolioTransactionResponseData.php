<?php

namespace Domain\Portfolio\Data;

use App\Models\SecuritySplit;
use App\Models\Transaction;
use App\Services\PortfolioSecuritySplitAdjustmentService;
use Domain\Transaction\Data\SplitAdjustedTransaction;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PortfolioTransactionResponseData extends Data
{
    public function __construct(
        public int $id,
        public string $externalTransactionId,
        public string $ticker,
        public string $name,
        public string $typeCode,
        public string $typeName,
        public float $numberOfShares,
        public float $pricePerShare,
        public float $totalAmount,
        public string $currencySymbol,
        public string $executedAt,
    ) {}

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @param  Collection<int, Collection<int, SecuritySplit>>  $splitsBySecurityId
     * @return array<int, self>
     */
    public static function fromTransactions(
        Collection $transactions,
        Collection $splitsBySecurityId,
        PortfolioSecuritySplitAdjustmentService $splitAdjustmentService,
    ): array {
        return $transactions
            ->map(function (Transaction $transaction) use ($splitAdjustmentService, $splitsBySecurityId): self {
                /** @var Collection<int, SecuritySplit> $splitsForSecurity */
                $splitsForSecurity = $splitsBySecurityId->get($transaction->security_id, collect());
                $splitAdjusted = SplitAdjustedTransaction::from(
                    $transaction,
                    $splitAdjustmentService->adjust($transaction, $splitsForSecurity),
                );

                return self::fromSplitAdjustedTransaction($splitAdjusted);
            })
            ->all();
    }

    private static function fromSplitAdjustedTransaction(SplitAdjustedTransaction $adjusted): self
    {
        $transaction = $adjusted->transaction;

        return new self(
            id: $transaction->id,
            externalTransactionId: $transaction->external_transaction_id ?? '',
            ticker: $transaction->security->ticker,
            name: $transaction->security->name,
            typeCode: $transaction->type->code->value,
            typeName: $transaction->type->name,
            numberOfShares: $adjusted->numberOfShares,
            pricePerShare: $adjusted->pricePerShare,
            totalAmount: $adjusted->totalAmount,
            currencySymbol: $transaction->currency->symbol,
            executedAt: $transaction->executed_at->toIso8601String(),
        );
    }
}
